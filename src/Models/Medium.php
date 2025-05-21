<?php

namespace Feeldee\MediaBox\Models;

use Feeldee\Framework\Facades\MimeType;
use Feeldee\Framework\Facades\Path;
use Feeldee\Framework\Models\SetUser;
use Hashids\Hashids;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\HttpFoundation\StreamedResponse;

class Medium extends Model
{
    use HasFactory, SetUser, MediaBoxFilesystemAdapter;

    /**
     * 複数代入可能な属性
     *
     * @var array
     */
    protected $fillable = ['size', 'width', 'height', 'content_type', 'subdirectory', 'filename', 'uri', 'rounds', 'uploaded_at'];

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
        static::created(function (Media $media) {
            // URI生成
            $media->encode();
        });

        static::deleted(function (Media $media) {
            // メディアファイル削除
            $media->deleteFile();
        });
    }

    /**
     * メディアをエンコードしてURIを生成します。
     */
    protected function encode(): void
    {
        $extension = MimeType::toExtension($this->content_type);
        $salt = config('app.key');
        // 注）URLエンコード対象の文字は使用しない
        $hashids = new Hashids($salt, 240, 'abcdefghijklmnopqrstuvwxyz1234567890_-');
        $this->uri = $hashids->encode($this->id, $this->rounds) . '.' . $extension;
        $this->save(['timestamps' => false]);
    }

    /**
     * メディアを保管しているメディアボックスを取得
     */
    public function mediaBox()
    {
        return $this->belongsTo(MediaBox::class);
    }

    /**
     * パス
     *
     * @return Attribute
     */
    protected function path(): Attribute
    {
        return Attribute::make(
            get: fn($value) => Path::combine($this->mediaBox->directory, $this->uri)
        );
    }

    /**
     * ソース
     *
     * @return Attribute
     */
    protected function src(): Attribute
    {
        return Attribute::make(
            get: fn($value) => MediaBox::url($this->path)
        );
    }

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
     * メディアファイルを削除します。
     * 
     * @return void
     */
    public function deleteFile(): void
    {
        self::disk()->delete($this->path);
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
