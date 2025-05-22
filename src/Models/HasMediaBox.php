<?php

namespace Feeldee\MediaBox\Models;

trait HasMediaBox
{
    /**
     * メディアボックス
     */
    public function mediaBox()
    {
        return $this->hasOne(MediaBox::class, 'user_id', 'id');
    }
}
