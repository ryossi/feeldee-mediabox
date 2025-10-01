<?php

namespace Tests\Feature\Model;

use Feeldee\MediaBox\Models\MediaBox;
use Feeldee\MediaBox\Models\MediaContent;
use Tests\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

class MediaContentTest extends TestCase
{

    use RefreshDatabase;

    /**
     * メディアコンテンツパスを条件にメディアコンテンツを検索できることを確認します。
     */
    public function test_where_path()
    {

        // 準備
        $user = User::create([
            'name' => 'テストユーザ',
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);
        $this->actingAs($user);
        $mediaBox = MediaBox::factory()->create([
            'user_id' => $user->id,
        ]);
        MediaContent::factory()->create([
            'media_box_id' => $mediaBox->id,
            'uri' => 'uri_content1.jpg',
        ]);
        $content2 = MediaContent::factory()->create([
            'media_box_id' => $mediaBox->id,
            'uri' => 'uri_content2.jpg',
        ]);
        MediaContent::factory()->create([
            'media_box_id' => $mediaBox->id,
            'uri' => 'uri_content3.jpg',
        ]);

        // 実行
        $contents = $mediaBox->mediaContents()
            ->wherePath($content2->path)
            ->first();

        // 評価
        $this->assertNotNull($contents);
        $this->assertEquals($content2->id, $contents->id);
    }

    /**
     * メディアコンテンツパスの条件がnullの場合でも、メディアコンテンツの検索がエラーとならないことを確認します。
     */
    public function test_where_path_null()
    {
        // 準備
        $user = User::create([
            'name' => 'テストユーザ',
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);
        $this->actingAs($user);
        $mediaBox = MediaBox::factory()->create([
            'user_id' => $user->id,
        ]);
        MediaContent::factory()->create([
            'media_box_id' => $mediaBox->id,
            'uri' => 'uri_content1.jpg',
        ]);
        MediaContent::factory()->create([
            'media_box_id' => $mediaBox->id,
            'uri' => 'uri_content2.jpg',
        ]);
        MediaContent::factory()->create([
            'media_box_id' => $mediaBox->id,
            'uri' => 'uri_content3.jpg',
        ]);

        // 実行
        $contents = $mediaBox->mediaContents()
            ->wherePath(null)
            ->first();

        // 評価
        $this->assertNull($contents);
    }

    /**
     * メディアコンテンツフィルタ
     * 
     * - 条件に一致するメディアコンテンツのみをフィルタリングすることができることを確認します。
     * - メディアコンテンツアップロード日時（=）でフィルタリングできることを確認します。
     * 
     * @link https://github.com/ryossi/feeldee-tracking/wiki/メディアコンテンツ#メディアコンテンツフィルタ
     */
    public function test_mediaBox_filter_uploaded_at()
    {
        // 準備
        Auth::shouldReceive('id')->andReturn(1);
        $mediaBox = MediaBox::factory(['user_id' => 1, 'directory' => 'mbox/123'])->has(MediaContent::factory()->count(3)->sequence(
            ['uploaded_at' => '2025-10-01 00:00:00'],
            ['uploaded_at' => '2025-10-01 12:00:00'],
            ['uploaded_at' => '2025-10-01 23:59:59'],
        ))->create();

        // 実行
        $mediaContent = MediaContent::filter("uploaded_at='2025-10-01 12:00:00'")->get();

        // 評価
        $this->assertCount(1, $mediaContent);
        $this->assertEquals('2025-10-01 12:00:00', $mediaContent[0]->uploaded_at);
    }

    /**
     * メディアコンテンツフィルタ
     * 
     * - 条件に一致するメディアコンテンツのみをフィルタリングすることができることを確認します。
     * - メディアコンテンツアップロード日時の範囲（<、>）でフィルタリングできることを確認します。
     * - フィルタ条件は「&」繋いだ文字列で指定できることを確認します。
     * - 複数の対象項目を指定した場合にはAND条件となることを確認します。
     * 
     * @link https://github.com/ryossi/feeldee-tracking/wiki/メディアコンテンツ#メディアコンテンツフィルタ
     */
    public function test_mediaBox_filter_uploaded_at_range()
    {
        // 準備
        Auth::shouldReceive('id')->andReturn(1);
        $mediaBox = MediaBox::factory(['user_id' => 1, 'directory' => 'mbox/123'])->has(MediaContent::factory()->count(3)->sequence(
            ['uploaded_at' => '2025-10-01 00:00:00'],
            ['uploaded_at' => '2025-10-01 12:00:00'],
            ['uploaded_at' => '2025-10-01 23:59:59'],
        ))->create();

        // 実行
        $mediaContents = $mediaBox->mediaContents()->filter("uploaded_at>'2025-10-01 00:00:00'&uploaded_at<'2025-10-01 23:59:59'")->get();

        // 評価
        $this->assertCount(1, $mediaContents);
        $this->assertEquals('2025-10-01 12:00:00', $mediaContents[0]->uploaded_at);
    }

    /**
     * メディアコンテンツフィルタ
     * 
     * - 条件に一致するメディアコンテンツのみをフィルタリングすることができることを確認します。
     * - メディアコンテンツアップロード日時の範囲（<=、>=）でフィルタリングできることを確認します。
     * - フィルタ条件は配列で指定できることを確認します。
     * - 複数の対象項目を指定した場合にはAND条件となることを確認します。
     */
    public function test_mediaBox_filter_uploaded_at_range_equality()
    {
        // 準備
        Auth::shouldReceive('id')->andReturn(1);
        $mediaBox = MediaBox::factory(['user_id' => 1, 'directory' => 'mbox/123'])->has(MediaContent::factory()->count(3)->sequence(
            ['uploaded_at' => '2025-10-01 00:00:00'],
            ['uploaded_at' => '2025-10-01 12:00:00'],
            ['uploaded_at' => '2025-10-01 23:59:59'],
        ))->create();

        // 実行
        $mediaContents = $mediaBox->mediaContents()->filter(['uploaded_at>="2025-10-01 00:00:00"', 'uploaded_at<="2025-10-01 23:59:59"'])->get();

        // 評価
        $this->assertCount(3, $mediaContents);
        $this->assertEquals('2025-10-01 23:59:59', $mediaContents[0]->uploaded_at);
        $this->assertEquals('2025-10-01 12:00:00', $mediaContents[1]->uploaded_at);
        $this->assertEquals('2025-10-01 00:00:00', $mediaContents[2]->uploaded_at);
    }
}
