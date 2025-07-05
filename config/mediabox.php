<?php

return [
    // メディアボックスプレフィックス
    'prefix' => env('FEELDEE_MEDIA_BOX_PREFIX', 'mbox'),
    // メディアボックス最大サイズ（MB単位）
    'max_size' => env('FEELDEE_MEDIA_BOX_MAX_SIZE', 100),
    // メディアコンテンツURIソルト
    'uri_salt' => env('FEELDEE_MEDIA_BOX_URI_SALT', env('APP_KEY', '')),
    // メディアボックスディスク（FILESYSTEM_DISKを使用する場合はnullまたはキーを削除）
    'disk' => env('FEELDEE_MEDIA_BOX_DISK', null),
    // アップロードイメージ最大幅（制限する場合、ピクセル値を数値で指定、制限しない場合はnullまたはキーを削除）
    'upload_image_max_width' =>  null,
    // メディアボックスとユーザとの関連付けタイプ（aggregationまたはcomposition）
    'user_relation_type' => 'aggregation'
];
