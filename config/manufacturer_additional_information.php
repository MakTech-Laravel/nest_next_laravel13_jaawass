<?php

return [

    'expires_days' => (int) env('MANUFACTURER_ADDITIONAL_INFO_EXPIRES_DAYS', 7),

    'submission_path' => 'manufacturer/additional-information',

    'max_file_sizes_kb' => [
        'image' => (int) env('MANUFACTURER_ADDITIONAL_INFO_MAX_IMAGE_KB', 10240),
        'video' => (int) env('MANUFACTURER_ADDITIONAL_INFO_MAX_VIDEO_KB', 51200),
        'audio' => (int) env('MANUFACTURER_ADDITIONAL_INFO_MAX_AUDIO_KB', 20480),
        'document' => (int) env('MANUFACTURER_ADDITIONAL_INFO_MAX_DOCUMENT_KB', 10240),
    ],

    'allowed_mimes' => [
        'image' => ['jpeg', 'jpg', 'png', 'gif', 'webp'],
        'video' => ['mp4', 'webm', 'mov', 'quicktime', 'x-msvideo', 'avi', 'mkv', 'x-matroska', 'm4v'],
        'audio' => ['mp3', 'mpeg', 'wav', 'x-wav', 'm4a', 'x-m4a', 'aac'],
        'document' => ['pdf', 'msword', 'vnd.openxmlformats-officedocument.wordprocessingml.document'],
    ],

    'storage_paths' => [
        'image' => 'manufacturer/additional-information/images',
        'video' => 'manufacturer/additional-information/videos',
        'audio' => 'manufacturer/additional-information/audio',
        'document' => 'manufacturer/additional-information/documents',
    ],

];
