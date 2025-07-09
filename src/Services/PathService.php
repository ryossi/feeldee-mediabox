<?php

namespace Feeldee\MediaBox\Services;

class PathService
{
    /**
     * OSの違いを吸収してパスを連結します。
     * 
     * @param $path 連結するパス
     * @return mixed 連結後のパス
     */
    public function combine(...$path): mixed
    {
        $combine = function ($base, $path) {
            $base = str_replace('/', DIRECTORY_SEPARATOR, $base);
            $base = str_replace('\\', DIRECTORY_SEPARATOR, $base);
            $path = str_replace('/', DIRECTORY_SEPARATOR, $path);
            $path = str_replace('\\', DIRECTORY_SEPARATOR, $path);

            if (empty($base)) {
                return $path;
            } elseif (empty($path)) {
                return $base;
            } else {
                if (!empty($base) && substr($base, -1) == DIRECTORY_SEPARATOR) $base = substr($base, 0, -1);
                if (!empty($path) && substr($path, 0, 1) == DIRECTORY_SEPARATOR) $path = substr($path, 1);
                return $base . DIRECTORY_SEPARATOR . $path;
            }
        };

        $combined = '/';
        foreach ($path as $one) {
            $combined = $combine($combined, $one);
        }

        return $combined;
    }
}
