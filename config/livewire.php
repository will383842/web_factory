<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Temporary File Upload
    |--------------------------------------------------------------------------
    |
    | Livewire FileUpload (used by Filament v4) issues a pre-signed URL when
    | the configured disk supports it (S3, MinIO). The PUT then bypasses
    | Laravel and goes directly browser → storage.
    |
    | In our setup the storage disk's hostname is `wf-minio:9000` which is
    | a Docker-internal name not resolvable from the host browser, so the
    | direct upload fails with ERR_NAME_NOT_RESOLVED. Forcing the temporary
    | upload disk to `local` makes Livewire post the file through Laravel
    | instead — slower but works without DNS gymnastics.
    |
    | Switch to `s3` (or another S3-compatible) once you've configured a
    | publicly-resolvable AWS_URL.
    |
    */

    'temporary_file_upload' => [
        'disk' => 'local',
        'rules' => null,           // string|array — ['file', 'mimes:png,jpg,zip', 'max:51200']
        'directory' => null,       // string — 'livewire-tmp' if null
        'middleware' => null,
        'preview_mimes' => [
            'png', 'gif', 'bmp', 'svg', 'wav', 'mp4',
            'mov', 'avi', 'wmv', 'mp3', 'm4a',
            'jpg', 'jpeg', 'mpga', 'webp', 'wma',
        ],
        'max_upload_time' => 5,    // minutes
        'cleanup' => true,
    ],

];
