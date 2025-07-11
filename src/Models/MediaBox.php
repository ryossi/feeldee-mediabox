<?php

namespace Feeldee\MediaBox\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Carbon\Carbon;
use Exception;
use Feeldee\Framework\Exceptions\ApplicationException;
use Feeldee\Framework\Models\SetUser;
use Feeldee\MediaBox\Facades\Path;
use Hashids\Hashids;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

class MediaBox extends Model
{
    use HasFactory, SetUser;

    /**
     * メディアボックスプレフィックスコンフィグレーションキー
     */
    const CONFIG_KEY_PREFIX = 'mediabox.prefix';

    /**
     * メディアボックス最大サイズコンフィグレーションキー
     */
    const CONFIG_KEY_MAX_SIZE = 'mediabox.max_size';

    /**
     * メディアボックスディスクコンフィグレーションキー
     */
    const CONFIG_KEY_DISK = 'mediabox.disk';

    /**
     * アップロードイメージ最大幅コンフィグレーションキー
     */
    const CONFIG_KEY_UPLOAD_IMAGE_MAX_WIDTH = 'mediabox.upload_image_max_width';

    /**
     * メディアボックスとユーザとの関連付けタイプコンフィグレーションキー
     */
    const CONFIG_KEY_USER_RELATION_TYPE = 'mediabox.user_relation_type';

    /**
     * メディアボックスとユーザとの関連付けタイプ
     * 
     * メディアボックスはユーザに紐づきますが、ユーザが削除されてもメディアボックスは削除されません。
     */
    const USER_RELATION_TYPE_AGGREGATION = 'aggregation';

    /**
     * メディアボックスとユーザとの関連付けタイプ
     * 
     * ユーザが削除されると、メディアボックスも削除されます。
     */
    const USER_RELATION_TYPE_COMPOSITION = 'composition';

    /**
     * URIソルトコンフィグレーションキー
     */
    const CONFIG_KEY_URI_SALT = 'mediabox.uri_salt';

    /**
     * サポートMIMEマップマップコンフィグレーションキー
     */
    const CONFIG_KEY_SUPPORT_MIME_MAP = 'mediabox.support_mime_map';

    /**
     * メディアボックスが既に存在しているエラーコード
     */
    const ERROR_CODE_MEDIA_BOX_ALREADY_EXISTS = 83001;

    /**
     * メディアボックスに空き容量がないエラーコード
     */
    const ERROR_CODE_MEDIA_BOX_SIZE_EXCEEDED = 83002;

    /**
     * メディアボックスでサポートされていないMIMEタイプエラーコード
     */
    const ERROR_CODE_MEDIA_BOX_UNSUPPORTED_MIME_TYPE = 83003;

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
                throw new ApplicationException(self::ERROR_CODE_MEDIA_BOX_ALREADY_EXISTS, [
                    'user_id' => $mediaBox->user_id,
                ]);
            }
        });

        static::deleted(function (MediaBox $mediaBox) {
            // メディアボックスに紐づくメディアコンテンツを削除
            $mediaBox->mediaContents()->each(function (MediaContent $medium) {
                // メディアコンテンツ削除
                $medium->delete();
            });
            // ディレクトリ削除
            $mediaBox->deleteDirectory();
        });
    }

    /**
     * メディアボックスディレクトリ
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
     * メディアボックス最大サイズ（MB単位）
     */
    protected function maxSize(): Attribute
    {
        return Attribute::make(
            get: fn($value) => is_null($value) ? config(self::CONFIG_KEY_MAX_SIZE) : $value,
        );
    }

    /**
     * メディアボックス使用済サイズ（バイト）
     */
    protected function usedSize(): Attribute
    {
        return Attribute::make(
            get: fn($value) => $this->mediaContents()->sum('size'),
        );
    }

    /**
     * メディアコンテンツリスト
     */
    public function mediaContents()
    {
        return $this->hasMany(MediaContent::class);
    }

    /**
     * メディアボックスディスク
     * 
     * メディアボックスにアップロードされたメディアコンテンツの保存先です。
     * 
     * @return FilesystemAdapter
     */
    public static function disk(): \Illuminate\Filesystem\FilesystemAdapter
    {
        if (config(MediaBox::CONFIG_KEY_DISK)) {
            return Storage::disk(config(MediaBox::CONFIG_KEY_DISK));
        }
        // デフォルトのストレージディスクを使用
        return Storage::disk();
    }

    /**
     * メディアボックスプレフィックス
     * 
     * メディアボックス全体を通じて使用するルートディレクトリです。
     * 
     * @return string プレフィックス
     */
    public static function prefix(): string
    {
        return config(self::CONFIG_KEY_PREFIX);
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
     * @return MediaContent メディアコンテンツ
     * @throws ApplicationException メディアボックスに空き容量がない場合
     */
    public function upload(mixed $data, string|null $filename = null, string|null $subdirectory = null,  Carbon|string|null $uploaded_at = null): MediaContent
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
        $max_width = config(MediaBox::CONFIG_KEY_UPLOAD_IMAGE_MAX_WIDTH);
        if ($max_width && $image->width() > $max_width) {
            // 縦横比を維持したまま指定した横幅に自動的に圧縮
            $image->widen($max_width);
        }

        // メディアファイル名
        if (empty($filename)) {
            // ファイル名が指定されていない場合は、メディアコンテンツのオリジナルファイル名を使用
            if ($data instanceof UploadedFile) {
                // アップロードファイルの場合
                $filename = $data->getClientOriginalName();
            } else if (is_string($data)) {
                // 文字列の場合は、パスからファイル名を取得
                $filename = basename($data);
            }
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
        // メディアコンテンツURI
        $support_mime_map = config(self::CONFIG_KEY_SUPPORT_MIME_MAP, []);
        $extension = isset($support_mime_map[$this->content_type]) ? $support_mime_map[$this->content_type] : false;
        if (!$extension) {
            // メディアボックスでサポートされていないMIMEタイプ
            throw new ApplicationException(
                MediaBox::ERROR_CODE_MEDIA_BOX_UNSUPPORTED_MIME_TYPE,
                ['mime_type' => $this->content_type]
            );
        }
        $salt = config(self::CONFIG_KEY_URI_SALT);
        // 注）URLエンコード対象の文字は使用しない
        $hashids = new Hashids($salt, 240, 'abcdefghijklmnopqrstuvwxyz1234567890_-');
        $uri = $hashids->encode($this->id, strtotime($uploaded_at)) . '.' . $extension;

        // メディアコンテンツ作成
        $mediumContent = $this->mediaContents()->create([
            'width' => $width,
            'height' => $height,
            'content_type' => $content_type,
            'subdirectory' => $subdirectory,
            'filename' => $filename,
            'uri' => $uri,
            'uploaded_at' => $uploaded_at,
        ]);

        // ファイルアップロード
        self::disk()->put($mediumContent->path, $image->stream());

        // メディアコンテンツサイズ
        try {
            $mediumContent->size = self::disk()->size($mediumContent->path);
            if (($this->used_size + $mediumContent->size) > $this->max_size * 1024 * 1024) {
                // メディアボックス使用済サイズとアップロードするコンテンツの合計がメディアボックス最大サイズ以上の場合
                throw new ApplicationException(self::ERROR_CODE_MEDIA_BOX_SIZE_EXCEEDED, [
                    'used_size' => $this->used_size,
                    'max_size' => $this->max_size,
                ]);
            }
            $mediumContent->save();
        } catch (Exception $e) {
            self::disk()->delete($mediumContent->path);
            throw $e;
        }

        return $mediumContent;
    }

    /**
     * メディアボックスパス変換
     *
     * 値がnullの場合は、nullを返却します。
     * 
     * 値がメディアコンテンツの場合は、メディアコンテンツパスを返却します。
     * 
     * 値がメディアコンテンツURLの場合は、メディアコンテンツパスの部分のみを返却します。
     *
     * その他の場合は、値を文字列に変換して返却します。
     * 
     * @param mixed $value 値
     * @return string|null パス
     */
    public static function path(mixed $value): string|null
    {
        if ($value === null) {
            // 値がnullの場合は、nullを返却
            return null;
        }
        if ($value instanceof MediaContent) {
            // メディアの場合は、パスを返却
            return $value->path;
        }
        if (is_string($value)) {
            // メディアコンテンツURLの場合は、パスを返却
            $path = parse_url($value, PHP_URL_PATH) ?? '';
            $prefix = self::prefix();
            $path = strstr($path, ($prefix[0] !== '/') ? ('/' . $prefix) : $prefix);
            if ($path !== false) {
                return $path;
            }
        }
        // その他の場合は、値を文字列に変換して返却
        return strval($value);
    }

    /**
     * メディアボックスURL変換
     * 
     * 値がnullの場合は、nullを返却します。
     * 
     * 値がメディアコンテンツの場合は、メディアコンテンツURLを返却します。
     * 
     * 値がメディアコンテンツパスの場合は、メディアボックスディスクを利用してメディアコンテンツURLに変換して返却します。
     * 
     * その他の場合は、値を文字列に変換して返却します。
     * 
     * @param mixed $value 値
     * @return string|null URL
     */
    public static function url(mixed $value): string|null
    {
        if ($value === null) {
            // 値がnullの場合は、nullを返却
            return null;
        }
        if ($value instanceof MediaContent) {
            // メディアの場合は、URLを返却
            return $value->url;
        }
        if (is_string($value)) {
            // 先頭が'/'で始まる場合削除
            $value = ltrim($value, '/');
            if (str_starts_with($value, self::prefix())) {
                // メディアコンテンツパスの場合は、URLを返却
                return self::disk()->url($value);
            }
        }
        // その他の場合は、値を文字列に変換して返却
        return strval($value);
    }

    // ========================== ここまで整理ずみ ==========================

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
     * メディアボックスからメディアリストを検索します。
     * メディアリストは、アップロード日時降順で取得します。
     * 
     * @param $filter フィルタ（条件式をカラム名=値&カラム名=値のように&で繋げて記述、条件式は=、>=、<=の3つが使用可能で全てAND条件）
     * @return Collection コレクション
     */
    public function search($filter = ""): Collection
    {
        $query = $this->mediaContents();

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
     * パスを指定してメディアを取得します。
     * パスに一致するメディアが存在しない場合は、nullを返却します。
     * 
     * @param  ?string $path パス
     * @return MediaContent|null メディアコンテンツまたはnull
     */
    public function find(?string $path): MediaContent|null
    {
        if (empty($path) || strpos($path, self::prefix()) === false) {
            return null;
        }
        $basename = basename($path);
        return $this->mediaContents()->uri($basename)->first();
    }
}
