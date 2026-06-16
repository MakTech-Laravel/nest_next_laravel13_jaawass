<?php

return [
    'attachments' => [
        'disk' => env('ORDER_ATTACHMENTS_DISK', 'public'),
        'max_photos' => (int) env('ORDER_ATTACHMENTS_MAX_PHOTOS', 5),
        'max_files' => (int) env('ORDER_ATTACHMENTS_MAX_FILES', 5),
        'max_photo_kb' => (int) env('ORDER_ATTACHMENTS_MAX_PHOTO_KB', 5120),
        'max_file_kb' => (int) env('ORDER_ATTACHMENTS_MAX_FILE_KB', 10240),
        'photo_extensions' => ['jpg', 'jpeg', 'png', 'webp'],
        'file_extensions' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv'],
    ],
];
