<?php

namespace Feeldee\MediaBox\Services;

/**
 * メディアボックスサービス
 */
class MediaBoxService
{

    /**
     * フォーマットサイズ
     * 
     * 以下のルールに従ってサイズをフォーマットします。
     * 
     * - 1KB未満の場合は、バイト単位で表示
     * - 1KB以上、1MB未満の場合は、KB単位で表示
     * - 1MB以上、1GB未満の場合は、MB単位で表示
     * - 1GB以上、1TB未満の場合は、GB単位で表示
     * - 1TB以上、1PB未満の場合は、TB単位で表示
     * - 1PB以上、1EB未満の場合は、PB単位で表示
     * - 1EB以上の場合は、EB単位で表示
     * 
     * 精度はデフォルトで0に設定されていますが、必要に応じて変更できます。
     * 
     * @param mixed $bytes サイズ（バイト単位）
     * @param integer $precision　精度
     * @return mixed フォーマットされたサイズ、数値でない場合はそのまま返却
     */
    public function formatSize(mixed $bytes, $precision = 0): mixed
    {
        if (!is_numeric($bytes)) {
            return $bytes;
        }

        if (abs($bytes) < 1024) {
            // 1KB未満の場合は、バイト単位で表示
            $precision = 0;
        }

        $units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB');

        if ($bytes < 0) {
            $sign = '-';
            $bytes = abs($bytes);
        } else {
            $sign = '';
        }

        $exp   = floor(log($bytes) / log(1024));
        $exp   = min($exp, count($units) - 1); // 最大の単位を超えないようにする
        $exp   = max($exp, 0); // 最小の単位は0（バイト）にする
        $unit  = $units[$exp];
        $bytes = $bytes > 0 ? $bytes / pow(1024, floor($exp)) : $bytes;
        $bytes = sprintf('%.' . $precision . 'f', $bytes);
        return $sign . $bytes . ' ' . $unit;
    }
}
