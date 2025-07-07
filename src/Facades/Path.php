<?php

namespace Feeldee\MediaBox\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Feeldee\MediaBox\Facades\Path
 *
 * @method static mixed combine(...$path)
 */
class Path extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Feeldee\MediaBox\Services\PathService::class;
    }
}
