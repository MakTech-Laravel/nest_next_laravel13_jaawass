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

    /*
    |--------------------------------------------------------------------------
    | Support ticket auto-reply
    |--------------------------------------------------------------------------
    |
    | When enabled, a canned acknowledgment is posted (and emailed via the
    | support-ticket-auto-reply template) after a customer replies on a ticket.
    | Ticket creation is not auto-replied — notifyCreated already covers that.
    |
    */
    'auto_reply' => [
        'enabled' => (bool) env('TICKET_AUTO_REPLY_ENABLED', true),
        // Optional: fixed sender user id. When null, assignee or first admin is used.
        'user_id' => env('TICKET_AUTO_REPLY_USER_ID') !== null && env('TICKET_AUTO_REPLY_USER_ID') !== ''
            ? (int) env('TICKET_AUTO_REPLY_USER_ID')
            : null,
    ],
];
