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
        if (config('mediabox.disk')) {
            return Storage::disk(config('mediabox.disk'));
        }
        // デフォルトのストレージディスクを使用
        return Storage::disk();
    }
}
