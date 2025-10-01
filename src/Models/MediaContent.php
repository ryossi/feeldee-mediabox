<?php

namespace Feeldee\MediaBox\Models;

use Carbon\Carbon;
use Feeldee\Framework\Models\SetUser;
use Illuminate\Database\Eloquent\Builder;
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
        // デフォルトの並び順は、メディアコンテンツアップロード日時降順
        static::addGlobalScope('order_number', function ($builder) {
            $builder->orderBy('uploaded_at', 'desc');
        });

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
        $this->mediaBox->disk()->delete($this->path);
    }

    /**
     * メディアコンテンツパスの条件を追加するためのローカルスコープ
     *
     * @param Builder $query
     * @param string|null $path メディアコンテンツパス
     */
    public function scopeWherePath(Builder $query, ?string $path): void
    {
        if (!empty($path) && strpos($path, MediaBox::prefix()) !== false) {
            $uri = basename($path);
        } else {
            $uri = $path;
        }
        $query->where('uri', $uri);
    }

    /**
     * メディアコンテンツフィルタのためのローカルスコープ
     * 
     * @return \Illuminate\Database\Eloquent\Builder
     * @param array|string|null $filters フィルタ条件
     * @see https://github.com/ryossi/feeldee-mediabox/wiki/メディアコンテンツ#メディアコンテンツフィルタ
     */
    public function scopeFilter(Builder $query, array|string|null $filters): void
    {
        if (is_string($filters)) {
            // 文字列の場合は、&で分割して配列に変換
            $filters = array_map('trim', explode('&', $filters));
        }
        if (is_array($filters)) {
            foreach ($filters as $filter) {
                if (str_starts_with($filter, 'uploaded_at')) {
                    // メディアコンテンツアップロード日時によるフィルタリング
                    // セパレータ（>, <, >=, <=, =）を抽出
                    if (preg_match('/uploaded_at(\>=|\<=|>|<|=)(.+)/', $filter, $matches)) {
                        $operator = $matches[1];
                        $uploaded_at = trim($matches[2], " \t\n\r\0\x0B'\"");
                        $query->where('uploaded_at', $operator, Carbon::parse($uploaded_at));
                    }
                }
            }
        }
    }
}
