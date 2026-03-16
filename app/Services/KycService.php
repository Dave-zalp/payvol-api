<?php

namespace App\Services;

use App\Jobs\Kyc\FetchBvnDetailsJob;
use App\Jobs\Kyc\FetchNinDetailsJob;
use App\Jobs\Kyc\VerifyBvnJob;
use App\Jobs\Kyc\VerifyNinJob;
use App\Models\KycVerification;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class KycService
{
    public function submitKyc($user, array $data)
    {
        $kyc = KycVerification::updateOrCreate(
            ['user_id' => $user->id],
            [
                'bvn_number' => $data['bvn_number'] ?? null,
                'nin_number' => $data['nin_number'] ?? null,
                'dob' => $data['dob'] ?? null,
                'home_address'  => $data['home_address'],
                'state'  => $data['state'],
                'city'  => $data['city'],
                'zip_code'  => $data['zip_code'],

                'selfie_image' => isset($data['selfie_image'])
                    ? $this->storeSecureImage($data['selfie_image'], 'selfies')
                    : null,

                'nin_front' => isset($data['nin_front'])
                    ? $this->storeSecureImage($data['nin_front'], 'nin_front')
                    : null,

                'nin_back' => isset($data['nin_back'])
                    ? $this->storeSecureImage($data['nin_back'], 'nin_back')
                    : null,

                'status' => 'pending'
            ]
        );

        Bus::chain([
            new VerifyBvnJob($kyc->id),
            new VerifyNinJob($kyc->id),
           // new FetchBvnDetailsJob($kyc->id),  // Doesn't work cos feature is not allowed
          //  new FetchNinDetailsJob($kyc->id),   // Doesn't work cos feature is not allowed
        ])->dispatch();


        return $kyc;
    }

    private function storeSecureImage(UploadedFile $file, string $folder)
    {
        if (!$file || !$file->isValid()) {
            throw new \Exception('Invalid upload');
        }

        /*
        |--------------------------------------------------------------------------
        | File Size Limit (2MB)
        |--------------------------------------------------------------------------
        */
        $maxSize = 2 * 1024 * 1024;

        if ($file->getSize() > $maxSize) {
            throw new \Exception('File too large');
        }

        /*
        |--------------------------------------------------------------------------
        | Extension Whitelist
        |--------------------------------------------------------------------------
        */
        $allowedExtensions = ['jpg','jpeg','png','webp'];

        $extension = strtolower($file->getClientOriginalExtension());

        if (!in_array($extension, $allowedExtensions)) {
            throw new \Exception('Invalid file extension');
        }

        /*
        |--------------------------------------------------------------------------
        | MIME Type Validation
        |--------------------------------------------------------------------------
        */
        $allowedMime = [
            'image/jpeg',
            'image/png',
            'image/webp'
        ];

        $mime = $file->getMimeType();

        if (!in_array($mime, $allowedMime)) {
            throw new \Exception('Invalid MIME type');
        }

        /*
        |--------------------------------------------------------------------------
        | Real Image Validation (Prevents fake images)
        |--------------------------------------------------------------------------
        */
        $imageInfo = @getimagesize($file->getRealPath());

        if ($imageInfo === false) {
            throw new \Exception('File is not a valid image');
        }

        /*
        |--------------------------------------------------------------------------
        | Dimension Limit (Prevent image bombs)
        |--------------------------------------------------------------------------
        */
        $maxWidth = 5000;
        $maxHeight = 5000;

        if ($imageInfo[0] > $maxWidth || $imageInfo[1] > $maxHeight) {
            throw new \Exception('Image dimensions too large');
        }

        /*
        |--------------------------------------------------------------------------
        | Generate Secure Filename
        |--------------------------------------------------------------------------
        */
        $filename = Str::uuid()->toString() . '.' . $extension;

        /*
        |--------------------------------------------------------------------------
        | Store in PRIVATE disk
        |--------------------------------------------------------------------------
        */
        $path = $file->storeAs("kyc/{$folder}", $filename, 'private');

        return $path;
    }
}
