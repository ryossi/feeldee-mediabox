<?php

namespace Feeldee\MediaBox\Models;

use Feeldee\Framework\Facades\ImageText;
use Feeldee\Framework\Facades\Path;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Carbon\Carbon;
use Exception;
use Feeldee\Framework\Exceptions\ApplicationException;
use Feeldee\Framework\Models\SetUser;
use Illuminate\Support\Collection;
use Intervention\Image\Facades\Image;

class MediaBox extends Model
{
    use HasFactory, SetUser, MediaBoxFilesystemAdapter;

    protected $fillable = ['user_id', 'directory', 'max_size'];

    /**
     * モデルの「起動」メソッド
     */
    protected static function booted(): void
    {
        static::saving(function (MediaBox $mediaBox) {
            // メディアボックスルートディレクトリデフォルト
            if (empty($mediaBox->attributes['directory'])) {
                $mediaBox->directory = md5($mediaBox->user_id);
            }
        });

        static::creating(function (MediaBox $mediaBox) {
            // メディアボックス存在チェック
            if (MediaBox::where('user_id', $mediaBox->user_id)->exists()) {
                throw new ApplicationException(83001, [
                    'user_id' => $mediaBox->user_id,
                ]);
            }
        });

        static::deleted(function (MediaBox $mediaBox) {
            // ディレクトリ削除
            $mediaBox->deleteDirectory();
        });
    }

    /**
     * コンテンツアップロード
     * 
     * コンテンツをメディアボックスにアップロードします。
     * 
     * @param mixed $data コンテンツデータ
     * @param string|null $filename メディアファイル名（デフォルトは、メディアコンテンツのオリジナルファイル名）
     * @param string|null $subdirectory メディアサブディレクトリ（デフォルトは、メディアコンテンツアップロード日時のyyyyMMdd形式）
     * @param mixed $uploaded_at アップロード日時（デフォルトは、システム日時）
     * @return Medium メディウム
     */
    public function upload(mixed $data, string|null $filename = null, string|null $subdirectory = null,  Carbon|string|null $uploaded_at = null): Medium
    {
        // アップロード日時のデフォルトはシステム日時
        if ($uploaded_at === null) {
            $uploaded_at = Carbon::now();
        } else if (is_string($uploaded_at)) {
            // 文字列の場合はCarbonインスタンスに変換
            $uploaded_at = Carbon::parse($uploaded_at);
        }

        // イメージ変換
        $image = Image::make($data);

        // 画像の最大幅をコンフィグレーションで制限
        $max_width = config('mediabox.image.max_width');
        if ($max_width && $image->width() > $max_width) {
            // 縦横比を維持したまま指定した横幅に自動的に圧縮
            $image->widen($max_width);
        }

        // メディアサブディレクトリ
        if (empty($subdirectory)) {
            // サブディレクトリが指定されていない場合は、アップロード日時のyyyyMMdd形式を使用
            $subdirectory = $uploaded_at->format('Ymd');
        }

        // メディアファイル名
        if (empty($filename)) {
            // ファイル名が指定されていない場合は、メディアコンテンツのオリジナルファイル名を使用
            $filename = $data instanceof UploadedFile ? $data->getClientOriginalName() : $image->basename();
        }

        // メディアコンテンツ幅
        $width = $image->width();

        // メディアコンテンツ高さ
        $height = $image->height();

        // メディアコンテンツタイプ
        if ($data instanceof UploadedFile) {
            $content_type = $data->getMimeType();
        } else {
            $content_type = $image->mime();
        }

        // メディア作成
        $medium = $this->media()->create([
            'width' => $width,
            'height' => $height,
            'content_type' => $content_type,
            'subdirectory' => $subdirectory,
            'filename' => $filename,
            'uploaded_at' => $uploaded_at,
        ]);

        // ファイルアップロード
        self::disk()->put($medium->path, $image->stream());

        // メディアコンテンツサイズ
        try {
            $medium->size = self::disk()->size($medium->path);
            $medium->save();
        } catch (Exception $e) {
            self::disk()->delete($medium->path);
            throw $e;
        }

        return $medium;
    }

    // ========================== ここまで整理ずみ ==========================

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
