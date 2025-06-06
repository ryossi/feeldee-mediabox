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
        return Storage::disk();
    }
}
