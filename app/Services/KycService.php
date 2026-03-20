<?php

namespace App\Services;

use App\Jobs\CreateStrowalletCustomerJob;
use App\Jobs\Kyc\FetchBvnDetailsJob;
use App\Jobs\Kyc\FetchNinDetailsJob;
use App\Jobs\Kyc\VerifyBvnJob;
use App\Jobs\Kyc\VerifyNinJob;
use App\Models\KycVerification;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class KycService
{
    public function submitKyc($user, array $data)
    {
        $selfie = isset($data['selfie_image'])
            ? $this->storeSecureImage($data['selfie_image'], 'selfies')
            : null;

        $ninFront = isset($data['nin_front'])
            ? $this->storeSecureImage($data['nin_front'], 'nin_front')
            : null;

        $ninBack = isset($data['nin_back'])
            ? $this->storeSecureImage($data['nin_back'], 'nin_back')
            : null;

        $kyc = KycVerification::updateOrCreate(
            ['user_id' => $user->id],
            [
                'bvn_number' => $data['bvn_number'] ?? null,
                'nin_number' => $data['nin_number'] ?? null,
                'date_of_birth' => $data['dob'] ?? null,
                'home_address'  => $data['home_address'],
                'state'  => $data['state'],
                'city'  => $data['city'],
                'zip_code'  => $data['zip_code'],

                'selfie_image_url' => $selfie['url'] ?? null,
                'selfie_image_public_id' => $selfie['public_id'] ?? null,

                'nin_front_url' => $ninFront['url'] ?? null,
                'nin_front_public_id' => $ninFront['public_id'] ?? null,

                'nin_back_url' => $ninBack['url'] ?? null,
                'nin_back_public_id' => $ninBack['public_id'] ?? null,

                'status' => 'pending'
            ]
        );

        Bus::chain([
            new VerifyBvnJob($kyc->id),  // Verify BVN Job
            new VerifyNinJob($kyc->id),  // Verify NIN Job
            new CreateStrowalletCustomerJob($user), // Create Customer Job
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

        // Size limit (2MB)
        $maxSize = 2 * 1024 * 1024;
        if ($file->getSize() > $maxSize) {
            throw new \Exception('File too large');
        }

        // Extension whitelist
        $allowedExtensions = ['jpg','jpeg','png','webp'];
        $extension = strtolower($file->getClientOriginalExtension());

        if (!in_array($extension, $allowedExtensions)) {
            throw new \Exception('Invalid file extension');
        }

        // MIME validation
        $allowedMime = [
            'image/jpeg',
            'image/png',
            'image/webp'
        ];

        if (!in_array($file->getMimeType(), $allowedMime)) {
            throw new \Exception('Invalid MIME type');
        }

        // Real image validation
        $imageInfo = @getimagesize($file->getRealPath());
        if ($imageInfo === false) {
            throw new \Exception('File is not a valid image');
        }

        // Dimension limit
        if ($imageInfo[0] > 5000 || $imageInfo[1] > 5000) {
            throw new \Exception('Image dimensions too large');
        }

        /*
        |--------------------------------------------------------------------------
        | Upload to Cloudinary
        |--------------------------------------------------------------------------
        */
        $result = Cloudinary::upload($file->getRealPath(), [
            'folder' => 'kyc/' . $folder,
            'type' => 'private',
            'resource_type' => 'image',
            'transformation' => [
                'quality' => 'auto',
                'fetch_format' => 'auto'
            ]
        ]);

        return [
            'url' => $result->getSecurePath(),
            'public_id' => $result->getPublicId(),
        ];
    }
}
