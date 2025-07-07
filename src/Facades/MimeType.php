<?php

namespace Feeldee\MediaBox\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Feeldee\MediaBox\Facades\MimeType
 *
 * @method static string|bool toExtension($mime_type)
 */
class MimeType extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Feeldee\MediaBox\Services\MimeTypeService::class;
    }
}
