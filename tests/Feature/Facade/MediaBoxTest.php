<?php

namespace Tests\Feature\Facade;

use Tests\TestCase;

class MediaBoxTest extends TestCase
{
    /**
     * フォーマットサイズ
     * 
     * サイズをフォーマットできることを確認します。
     */
    public function test_format_size()
    {
        $this->assertEquals('1 KB', \Feeldee\MediaBox\Facades\MediaBox::formatSize(1024));
        $this->assertEquals('1 MB', \Feeldee\MediaBox\Facades\MediaBox::formatSize(1024 * 1024));
        $this->assertEquals('1 GB', \Feeldee\MediaBox\Facades\MediaBox::formatSize(1024 * 1024 * 1024));
    }

    /**
     * フォーマットサイズ
     * 
     * 精度を指定してサイズをフォーマットできることを確認します。
     */
    public function test_format_size_with_precision()
    {
        $this->assertEquals('1.50 KB', \Feeldee\MediaBox\Facades\MediaBox::formatSize(1024 + 512, 2));
        $this->assertEquals('1.00 MB', \Feeldee\MediaBox\Facades\MediaBox::formatSize(1024 * 1024, 2));
        $this->assertEquals('1.00 GB', \Feeldee\MediaBox\Facades\MediaBox::formatSize(1024 * 1024 * 1024, 2));
    }

    /**
     * フォーマットサイズ
     * 
     * MediaBoxエイリアスを利用してbladeファイル内でサイズをフォーマットできることを確認します。
     */
    public function test_format_size_blade_alias()
    {
        $view = $this->blade(
            '<ul><li>{{ MediaBox::formatSize(1024) }}</li><li>{{ MediaBox::formatSize(1024 * 1024) }}</li><li>{{ MediaBox::formatSize(1024 * 1024 * 1024) }}</li></ul>'
        );
        $this->assertStringContainsString('<ul><li>1 KB</li><li>1 MB</li><li>1 GB</li></ul>', $view);
    }
}
