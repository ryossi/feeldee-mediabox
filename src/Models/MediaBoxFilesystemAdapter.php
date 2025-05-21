<?php

namespace Feeldee\MediaBox\Models;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;

trait MediaBoxFilesystemAdapter
{
    /**
     * 環境変数:MEDIA_BOX_DISKで指定したメディアボックスのファイルシステムディスクを取得します（デフォルト:mbox）。
     * 
     * @return FilesystemAdapter
     */
    protected static function disk(): FilesystemAdapter
    {
        return Storage::disk();
    }
}
