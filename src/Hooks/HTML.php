<?php

namespace Feeldee\MediaBox\Hooks;

use Feeldee\MediaBox\Models\MediaBox;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use PHPHtmlParser\Dom;

class HTML implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $key
     * @param  mixed  $value
     * @param  array  $attributes
     * @return mixed
     */
    public function get($model, string $key, $value, array $attributes)
    {
        if ($value == null) {
            return null;
        }
        $dom = new Dom;
        $dom->loadStr($value);
        $images = $dom->find('img');
        foreach ($images as $image) {
            $image->setAttribute('src', MediaBox::url($image->src));
        }
        return $dom->outerHtml;
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $key
     * @param  mixed  $value
     * @param  array  $attributes
     * @return mixed
     */
    public function set($model, string $key, $value, array $attributes)
    {
        if ($value == null) {
            $text = null;
        } else {
            $dom = new Dom;
            $dom->loadStr($value);
            $images = $dom->find('img');
            foreach ($images as $image) {
                $image->setAttribute('src', MediaBox::path($image->src));
            }
            $value = $dom->outerHtml;
        }
        return [
            'value' => $value,
        ];
    }
}
