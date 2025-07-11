<?php

namespace Feeldee\MediaBox\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Feeldee\MediaBox\Facades\ImageText
 *
 * @method static string resize(string $text, mixed $width = null, mixed $height = null, int $quality = 90)
 */
class ImageText extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Feeldee\Framework\Services\ImageTextService::class;
    }
}
