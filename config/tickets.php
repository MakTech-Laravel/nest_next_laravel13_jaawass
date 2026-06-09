<?php

return [
    'attachments' => [
        'disk' => env('TICKET_ATTACHMENTS_DISK', 'public'),
        'max_per_message' => (int) env('TICKET_MAX_ATTACHMENTS_PER_MESSAGE', 5),
        'max_file_kb' => (int) env('TICKET_MAX_FILE_KB', 10240),
        'allowed_extensions' => [
            'jpg',
            'jpeg',
            'png',
            'gif',
            'webp',
            'pdf',
            'doc',
            'docx',
            'xls',
            'xlsx',
            'ppt',
            'pptx',
            'txt',
            'csv',
            'zip',
        ],
    ],
];
