<?php

/*
 * This file is part of the Laravel Cloudinary package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

return [

    'notification_url' => env('CLOUDINARY_NOTIFICATION_URL'),

    'cloud_url' => env(
        'CLOUDINARY_URL',
        'cloudinary://'.env('CLOUDINARY_API_KEY').':'.env('CLOUDINARY_API_SECRET').'@'.env('CLOUDINARY_CLOUD_NAME')
    ),

    // ✅ ADD THIS BLOCK (VERY IMPORTANT)
    'cloud' => [
        'cloud_name' => env('CLOUDINARY_CLOUD_NAME'),
        'api_key'    => env('CLOUDINARY_API_KEY'),
        'api_secret' => env('CLOUDINARY_API_SECRET'),
    ],

    'upload_preset' => env('CLOUDINARY_UPLOAD_PRESET'),

    'upload_route' => env('CLOUDINARY_UPLOAD_ROUTE'),

    'upload_action' => env('CLOUDINARY_UPLOAD_ACTION'),
];
