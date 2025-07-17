<?php

namespace Feeldee\MediaBox\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Feeldee\MediaBox\Facades\MediaBox
 *
 * @method static mixed formatSize(mixed $bytes, $precision = 2)
 */
class MediaBox extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Feeldee\MediaBox\Services\MediaBoxService::class;
    }
}
