<?php

namespace Feeldee\MediaBox\Models;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;

trait MediaBoxFilesystemAdapter
{
    /**
     * メディアボックスディスク
     * 
     * @return FilesystemAdapter
     */
    protected static function disk(): FilesystemAdapter
    {
        if (config(MediaBox::CONFIG_KEY_DISK)) {
            return Storage::disk(config(MediaBox::CONFIG_KEY_DISK));
        }
        // デフォルトのストレージディスクを使用
        return Storage::disk();
    }
}
