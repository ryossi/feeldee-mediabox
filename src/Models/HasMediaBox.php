<?php

namespace Feeldee\MediaBox\Models;

use Illuminate\Database\Eloquent\Model;

trait HasMediaBox
{
    public static function bootHasMediaBox()
    {
        static::deleting(function (Model $model) {
            if (config(MediaBox::CONFIG_KEY_USER_RELATION_TYPE) === MediaBox::USER_RELATION_TYPE_COMPOSITION) {
                // 関連付けされたメディアボックスも同時に削除
                $model->mediaBox->delete();
            }
        });
    }

    /**
     * メディアボックス
     */
    public function mediaBox()
    {
        return $this->hasOne(MediaBox::class, 'user_id');
    }

    /**
     * ユーザがメディアボックスを持っているかどうか
     * 
     * @return bool 持っている場合はtrue、持っていない場合はfalse
     */
    public function hasMediaBox(): bool
    {
        return $this->mediaBox()->exists();
    }
}
