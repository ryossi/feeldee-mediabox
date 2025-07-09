<?php

return [

    /*
    |--------------------------------------------------------------------------
    | メディアボックスに関する設定
    |--------------------------------------------------------------------------
    |
    */
    // メディアボックスプレフィックス
    'prefix' => env('FEELDEE_MEDIA_BOX_PREFIX', 'mbox'),
    // メディアボックス最大サイズ（MB単位）
    'max_size' => env('FEELDEE_MEDIA_BOX_MAX_SIZE', 100),
    // メディアボックスディスク（FILESYSTEM_DISKを使用する場合はnullまたはキーを削除）
    'disk' => env('FEELDEE_MEDIA_BOX_DISK', null),
    // アップロードイメージ最大幅（制限する場合、ピクセル値を数値で指定、制限しない場合はnullまたはキーを削除）
    'upload_image_max_width' =>  null,
    // メディアボックスとユーザとの関連付けタイプ（aggregationまたはcomposition）
    'user_relation_type' => 'aggregation',

    /*
    |--------------------------------------------------------------------------
    | メディアに関する設定
    |--------------------------------------------------------------------------
    |
    */
    // メディアコンテンツURIソルト
    'media_content_uri_salt' => env('FEELDEE_MEDIA_CONTENT_URI_SALT', env('APP_KEY', '')),
];
