<?php

namespace Feeldee\MediaBox\Models;

use Feeldee\Framework\Facades\ImageText;
use Feeldee\Framework\Facades\Path;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Image;
use Exception;
use Carbon\Carbon;
use Feeldee\Framework\Models\SetUser;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MediaBox extends Model
{
    use HasFactory, SetUser, MediaBoxFilesystemAdapter;

    /**
     * モデルの「起動」メソッド
     */
    protected static function booted(): void
    {
        static::deleted(function (MediaBox $mediaBox) {
            // ディレクトリ削除
            $mediaBox->deleteDirectory();
        });
    }

    /**
     * 最大容量（MB）
     */
    protected function maxSize(): Attribute
    {
        return Attribute::make(
            get: fn($value) => is_null($value) ? config('feeldee.mediabox.max_size') : $value,
        );
    }

    /**
     * 容量（バイト）
     */
    protected function size(): Attribute
    {
        return Attribute::make(
            get: fn($value) => $this->media()->sum('size'),
        );
    }

    /**
     * ディレクトリ
     *
     * @return Attribute
     */
    protected function directory(): Attribute
    {
        return Attribute::make(
            get: fn($value) => Path::combine(self::prefix(), $value),
        );
    }

    /**
     * メディアボックスを作成します。
     * 
     * ユーザIDが未指定の場合は、 デフォルトでログイン中のユーザのIDを設定します。
     * 
     * ディレクトリ未指定の場合は、デフォルトでユーザIDのMD5ハッシュを設定します。
     * 
     * @param int|null ユーザID
     * @param string|null ディレクトリ
     * @return MediaBox|false 作成が成功した場合は、作成したメディアボックス、失敗した場合はfalse
     */
    public static function create(int|null $user_id = null, string|null $directory = null): Self|false
    {
        if (empty($user_id)) {
            // ユーザID未指定の場合

            // ログイン中のユーザのID
            $user_id = Auth::id();
        }

        if (empty($directory)) {
            // ディレクトリ未指定の場合

            // ユーザIDのMD5
            $directory = md5($user_id);
        }

        DB::transaction(function () use ($user_id, $directory) {
            // メディアボックス作成
            $mediaBox = new Self();
            $mediaBox->user_id = $user_id;
            $mediaBox->directory = $directory;
            $mediaBox->save();
            return $mediaBox;
        });

        return false;
    }

    /**
     * 容量をフォーマットします。
     * 
     * @param integer $precision　精度
     * @param array $units 単位配列
     */
    public function formatSize($precision = 2, array $units = null)
    {
        $bytes = $this->size;

        if (abs($bytes) < 1024) {
            $precision = 0;
        }

        if (is_array($units) === false) {
            $units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
        }

        if ($bytes < 0) {
            $sign = '-';
            $bytes = abs($bytes);
        } else {
            $sign = '';
        }

        $exp   = floor(log($bytes) / log(1024));
        $unit  = $units[$exp];
        $bytes = $bytes > 0 ? $bytes / pow(1024, floor($exp)) : $bytes;
        $bytes = sprintf('%.' . $precision . 'f', $bytes);
        return $sign . number_format($bytes) . ' ' . $unit;
    }

    /**
     * 使用率（%）
     * 
     * @param integer $precision　精度
     */
    public function usage($precision = 2): float
    {
        $usage = ($this->size / ($this->maxSize * 1024 * 1024)) * 100;
        return sprintf('%.' . $precision . 'f', $usage);
    }

    /**
     * メディアボックスに格納されているメディアリスト取得
     */
    public function media()
    {
        return $this->hasMany(Medium::class);
    }

    /**
     * メディアボックスからメディアリストを検索します。
     * メディアリストは、アップロード日時降順で取得します。
     * 
     * @param $filter フィルタ（条件式をカラム名=値&カラム名=値のように&で繋げて記述、条件式は=、>=、<=の3つが使用可能で全てAND条件）
     * @return Collection コレクション
     */
    public function search($filter = ""): Collection
    {
        $query = $this->media();

        // 条件式
        $conditions = explode('&', $filter);
        foreach ($conditions as $condition) {
            if (false !== strpos($condition, '>=')) {
                $where = explode('>=', $condition);
                $query->where(trim($where[0]), '>=', rtrim(ltrim($where[1])));
            } else if (false !== strpos($condition, '<=')) {
                $where = explode('<=', $condition);
                $query->where(trim($where[0]), '<=', rtrim(ltrim($where[1])));
            } else if (false !== strpos($condition, '=')) {
                $where = explode('=', $condition);
                $query->where(trim($where[0]), '=', rtrim(ltrim($where[1])));
            }
        }

        return $query->orderBy('uploaded_at', 'desc')->get();
    }

    /**
     * メディアデータをメディアボックスへアップロードします。
     * 
     * @param mixed $data メディアデータ
     * @param string $filename ファイル名
     * @param ?string $subdirectory サブディレクトリ
     * @param ?string $content_type コンテンツタイプ（デフォルトは、イメージのmime）
     * @param mixed $uploaded_at アップロード日時（デフォルトは、システム日時）
     * @return Medium メディア
     */
    public function upload(mixed $data, string $filename, ?string $subdirectory = null,  ?string $content_type = null, mixed $uploaded_at = null): Medium
    {
        // アップロード日時のデフォルトはシステム日時
        if ($uploaded_at === null) {
            $uploaded_at = Carbon::now();
        }

        // イメージ変換
        $image = Image::make($data);

        // コンテンツタイプ
        if (empty($content_type)) {
            $content_type = $image->mime();
        }

        // 同一メディア存在チェック
        $media = $this->media()->filename($filename)->subdirectory($subdirectory)->first();
        if ($media) {
            // 同一メデイアが存在する場合

            // メディア更新
            $media->width = $image->width();
            $media->height = $image->height();
            $media->content_type = $content_type;
            $media->subdirectory = $subdirectory;
            $media->filename = $filename;
            $media->uploaded_at = $uploaded_at;
        } else {
            // 同一メディアが存在しない場合

            // メディア作成
            $media = $this->media()->create([
                'width' => $image->width(),
                'height' => $image->height(),
                'content_type' => $content_type,
                'subdirectory' => $subdirectory,
                'filename' => $filename,
                'rounds' => 0,
                'uploaded_at' => $uploaded_at,
            ]);
        }

        // ファイルアップロード
        if (ImageText::isImageText($data)) {
            // イメージテキストの場合
            $path = self::disk()->putFileAs($this->directory, $data, $media->uri);
        } else {
            // イメージテキスト以外の場合
            $path = Path::combine($this->directory, $media->uri);
            self::disk()->put($path, $data);
        }

        // サイズ設定
        try {
            $media->size = self::disk()->size($path);
            $media->save();
        } catch (Exception $e) {
            self::disk()->delete($path);
            throw $e;
        }

        return $media;
    }

    /**
     * イメージファイルをメディアボックスへアップロードします。
     * イメージは、メディアボックスに関するコンフィグレーションのイメージ最大幅に従い圧縮されます。
     * 圧縮されたイメージファイルは、ランダムな一意の名前が割り当てられてアップロード日付ごとのフォルダに自動で振り分けれれます。
     * 
     * @param UploadedFile $file アップロードファイル
     * @return Medium メディア
     */
    public function uploadFile(UploadedFile $file): Medium
    {
        // イメージ圧縮
        $image = Image::make($file);
        $max_width = config('feeldee.mediabox.image.max_width');
        if ($image->width() > $max_width) {
            // イメージ圧縮
            $image->widen($max_width);
        }

        // メディア作成
        $uploaded_at = Carbon::now();
        $media = $this->media()->create([
            'width' => $image->width(),
            'height' => $image->height(),
            'content_type' => $file->getMimeType(),
            'subdirectory' => $uploaded_at->format('Ymd'),
            'filename' => $file->hashName(),
            'rounds' => 0,
            'uploaded_at' => $uploaded_at,
        ]);

        // ファイルアップロード
        self::disk()->put($media->path, $image->stream());

        // サイズ設定
        try {
            $media->size = self::disk()->size($media->path);
            $media->save();
        } catch (Exception $e) {
            self::disk()->delete($media->path);
            throw $e;
        }

        return $media;
    }

    /**
     * ディレクトリを削除します。
     * 
     * @return void
     */
    public function deleteDirectory(): void
    {
        self::disk()->deleteDirectory($this->directory);
    }

    /**
     * メディアボックスプレフィックス取得
     */
    public static function prefix(): string
    {
        return config('feeldee.mediabox.prefix');
    }

    /**
     * パスを指定してメディアを取得します。
     * パスに一致するメディアが存在しない場合は、nullを返却します。
     * 
     * @param  ?string $path パス
     * @return Medium|null メディアまたはnull
     */
    public function find(?string $path): Medium|null
    {
        if (empty($path) || strpos($path, self::prefix()) === false) {
            return null;
        }
        $basename = basename($path);
        return $this->media()->uri($basename)->first();
    }

    /**
     * メディアファイルのパスを取得します。
     * 値がメディアファイルでない場合は、そのまま返却します。
     * 
     * @param mixed $value 値
     * @return string|null パス
     */
    public static function path(mixed $value): string|null
    {
        if (is_null($value)) {
            return null;
        }
        if ($value instanceof Medium) {
            // メディアの場合
            return $value->path;
        }
        if (ImageText::isImageText($value)) {
            // イメージテキストは除外
            return $value;
        }
        $base_url = self::disk()->url(self::prefix());
        $value = preg_replace('/(https?:\/\/(www\.)?[0-9a-z\-\.]+:?[0-9]{0,5})/', '', $value);
        if (str_starts_with($value, $base_url)) {
            $path = strstr($value, self::prefix());
            if ($path !== false) {
                return $path;
            }
        }
        return $value;
    }

    /**
     * メディアファイルのURLを取得します。
     * パスがメディアファイルでない場合は、そのまま返却します。
     * 
     * @param @param mixed $path パス
     * @return string|null URL
     */
    public static function url(mixed $path): string|null
    {
        if (is_null($path)) {
            return null;
        }
        if (ImageText::isImageText($path)) {
            // イメージテキストは除外
            return $path;
        }
        if (str_starts_with($path, self::prefix())) {
            return self::disk()->url($path);
        } else {
            // メディアボックスのパスではない場合変換しない
            return $path;
        }
    }
}
