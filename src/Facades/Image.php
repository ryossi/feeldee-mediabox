<?php

namespace Feeldee\MediaBox\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Feeldee\MediaBox\Facades\Image
 *
 * @method static \Intervention\Image\Image create(string $data)
 * @method static string resize(string $text, mixed $width = null, mixed $height = null, int $quality = 90)
 * @method static string mimeType(string $data)
 */
class Image extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Feeldee\MediaBox\Services\ImageService::class;
    }
}
