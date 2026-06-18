<?php

return [

    'expires_days' => (int) env('MANUFACTURER_ADDITIONAL_INFO_EXPIRES_DAYS', 7),

    'submission_path' => 'manufacturer/additional-information',

    'max_file_sizes_kb' => [
        'image' => 10240,
        'video' => 51200,
        'audio' => 20480,
        'document' => 10240,
    ],

    'allowed_mimes' => [
        'image' => ['jpeg', 'jpg', 'png', 'gif', 'webp'],
        'video' => ['mp4', 'webm', 'mov', 'quicktime'],
        'audio' => ['mp3', 'mpeg', 'wav', 'x-wav', 'm4a', 'x-m4a', 'aac'],
        'document' => ['pdf', 'msword', 'vnd.openxmlformats-officedocument.wordprocessingml.document'],
    ],

];
