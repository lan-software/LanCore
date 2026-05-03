<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Body retention (days)
    |--------------------------------------------------------------------------
    |
    | Email rows persist forever for forensic / audit reasons (subject,
    | recipient, status, source). Rendered html_body and text_body are
    | truncated for rows older than this many days to keep storage bounded.
    | Set to 0 to disable body truncation.
    |
    */

    'body_retention_days' => (int) env('EMAIL_LOG_BODY_RETENTION_DAYS', 90),

];
