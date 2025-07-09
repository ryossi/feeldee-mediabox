<?php

namespace Feeldee\MediaBox\Services;

use Intervention\Image\Facades\Image;

/**
 * Base64形式のイメージテキストを取り扱うユーティリティサービス
 */
class ImageTextService
{
    /**
     * イメージテキストをリサイズします。
     * 
     * @param string $text イメージテキスト
     * @param mixed width 横幅
     * @param mixed height 縦幅
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
     * イメージテキストかどうかを判定します。
     * 
     * @param ?string $text 評価対象テキスト
     * @return bool イメージテキストの場合true、以外はfalse
     */
    public function isImageText(?string $text): bool
    {
        if (empty($text)) {
            return false;
        }
        if (50 > strlen($text)) {
            return false;
        }
        $substrBase64 = substr($text, 0, 50);
        return preg_match('/data\:image\/.*\;base64\,/', $substrBase64);
    }
}
