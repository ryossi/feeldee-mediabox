<?php

return [
    // メディアボックス最大容量
    'max_size' => env('FEELDEE_MEDIA_BOX_MAX_SIZE', 104857600), // 100MB
    // URIソルト
    'uri_salt' => env('FEELDEE_MEDIA_BOX_URI_SALT', env('APP_KEY', '')),
    // イメージ
    'image' => [
        // 最大幅（制限する場合、ピクセル値を数値で指定、制限しない場合はnullまたはキーを削除）
        'max_width' => null,
    ]
];
