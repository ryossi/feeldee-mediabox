<?php

namespace Tests\Feature\Model;

use Feeldee\MediaBox\Models\MediaBox;
use Feeldee\MediaBox\Models\MediaContent;
use Tests\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

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
}
