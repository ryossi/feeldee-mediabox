<?php

namespace Feeldee\MediaBox\Models;

use Feeldee\Framework\Models\SetUser;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MediaContent extends Model
{
    use HasFactory, SetUser;

    protected $fillable = ['subdirectory', 'filename', 'size', 'width', 'height', 'content_type', 'uri', 'uploaded_at'];

    protected $appends = ['src'];

    protected $visible = ['id', 'size', 'width', 'height', 'content_type', 'src', 'uploaded_at'];

    public static function create(array $attributes = [])
    {
        throw new \Exception("Please use the media box upload method instead.");
    }

    /**
     * モデルの「起動」メソッド
     */
    protected static function booted(): void
    {
        static::deleted(function (MediaContent $media) {
            // メディアコンテンツファイル削除
            $media->deleteFile();
        });
    }

    /**
     * メディアボックス
     */
    public function mediaBox()
    {
        return $this->belongsTo(MediaBox::class);
    }

    /**
     * メディアコンテンツパス
     *
     * @return Attribute
     */
    protected function path(): Attribute
    {
        return Attribute::make(
            get: fn($value) => rtrim($this->mediaBox->directory, DIRECTORY_SEPARATOR)
                . (isset($this->subdirectory) && $this->subdirectory !== '' ? DIRECTORY_SEPARATOR . trim($this->subdirectory, DIRECTORY_SEPARATOR) : '')
                . DIRECTORY_SEPARATOR . ltrim($this->uri, DIRECTORY_SEPARATOR)
        );
    }

    /**
     * メディアコンテンツURL
     *
     * @return Attribute
     */
    protected function url(): Attribute
    {
        return Attribute::make(
            get: fn($value) => $this->mediaBox->disk()->url(ltrim($this->path, '/'))
        );
    }

    /**
     * メディアコンテンツのファイルを削除します。
     * 
     * @return void
     */
    protected function deleteFile(): void
    {
        self::disk()->delete($this->path);
    }
}
