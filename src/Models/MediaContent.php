<?php

namespace Feeldee\MediaBox\Models;

use Feeldee\Framework\Models\SetUser;
use Feeldee\MediaBox\Facades\Path;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MediaContent extends Model
{
    use HasFactory, SetUser;

    /**
     * 複数代入可能な属性
     *
     * @var array
     */
    protected $fillable = ['subdirectory', 'filename', 'size', 'width', 'height', 'content_type', 'uploaded_at'];

    /**
     * 配列に追加する属性
     * 
     * @var array
     */
    protected $appends = ['src'];

    /**
     * 配列に表示する属性
     *
     * @var array
     */
    protected $visible = ['id', 'size', 'width', 'height', 'content_type', 'src', 'uploaded_at'];

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
            get: fn($value) => Path::combine($this->mediaBox->directory, $this->subdirectory, $this->uri)
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

    // ========================== ここまで整理ずみ ==========================

    /**
     * メディアファイルをダウンロードします。
     * 
     * @return StreamedResponse|null ファイルストリーム
     */
    public function downloadFile(): StreamedResponse
    {
        return self::disk()->download($this->path);
    }

    /**
     * ファイル名とサブディレクトリで絞り込むクエリのスコープを設定
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param string $filename ファイル名
     * @param ?string $subdirectory サブディレクトリ
     * 
     * @return void
     */
    public function scopeFilename($query, string $filename, ?string $subdirectory = null)
    {
        $query->where('filename', $filename);
    }

    /**
     * サブディレクトリで絞り込むクエリのスコープを設定
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param ?string $subdirectory サブディレクトリ
     * 
     * @return void
     */
    public function scopeSubdirectory($query, ?string $subdirectory)
    {
        if (is_null($subdirectory)) {
            $query->whereNull('subdirectory');
        } else {
            $subdirectory = str_starts_with($subdirectory, '/') ? ltrim($subdirectory, '/') : $subdirectory;
            $query->where('subdirectory', $subdirectory);
        }
    }

    /**
     * サブディレクトリで絞り込むクエリのスコープを設定
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param ?string $subdirectory サブディレクトリ
     * 
     * @return void
     */
    public function scopeUri($query, string $uri)
    {
        $query->where('uri', $uri);
    }
}
