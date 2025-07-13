<?php

namespace Feeldee\MediaBox\Services;

use Intervention\Image\Facades\Image;

/**
 * イメージユーティリティサービス
 */
class ImageService
{
    /**
     * \Intervention\Image\Image インスタンスを取得します。
     */
    public function create(string $data): \Intervention\Image\Image
    {
        return Image::make($data);
    }

    /**
     * イメージテキストをリサイズします。
     * 
     * @param string $text イメージテキスト
     * @param mixed $width 横幅
     * @param mixed $height 縦幅
     * @param int $quality 解像度（デフォルト90%）
     * @return string 変換後のイメージテキスト
     */
    public function resize(string $text, mixed $width = null, mixed $height = null, int $quality = 90): string
    {
        if ($width !== null && $height === null) {
            return (string)Image::make($text)->widen($width)->encode('data-url', $quality);
        }
        if ($width === null && $height !== null) {
            return (string)Image::make($text)->heighten($height)->encode('data-url', $quality);
        }
        return (string)Image::make($text)->resize($width, $height)->encode('data-url', $quality);
    }

    /**
     * 画像データまたはファイルパスからMIMEタイプを取得します。
     *
     * @param string $data ファイルパスまたはBase64エンコード画像データ
     * @return string|null MIMEタイプ（判別できない場合はnull）
     */
    public function mimeType(string $data): ?string
    {
        // ファイルパスの場合
        if (is_file($data)) {
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            return $finfo->file($data) ?: null;
        }

        // Base64データの場合（イメージファイルに限定しない）
        if (preg_match('/^data:([\w\/\.\-\+]+);base64,/', $data, $matches)) {
            return $matches[1];
        }

        // プレフィックスなしのBase64データの場合
        $decoded = base64_decode($data, true);
        if ($decoded !== false) {
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            return $finfo->buffer($decoded) ?: null;
        }

        return null;
    }
}
