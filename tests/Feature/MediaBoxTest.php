<?php

namespace Tests\Feature;

use Feeldee\Framework\Exceptions\ApplicationException;
use Feeldee\MediaBox\Models\MediaBox;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Auth\User;

class MediaBoxTest extends TestCase
{
    use RefreshDatabase;

    /**
     * メディアボックス作成
     * 
     * - ユーザのメディアボックスを作成できることを確認します。
     * 
     * @link https://github.com/ryossi/feeldee-tracking/wiki/メディアボックス#メディアボックス作成
     */
    public function test_mediaBox_create()
    {
        // 準備
        $user = new class extends User {
            public function getIdAttribute()
            {
                return 1;
            }
            public function getNameAttribute()
            {
                return 'test_user';
            }
        };
        $this->actingAs($user);
        $maxSize = 100 * 1024 * 1024; // 100MB

        // 実行
        $mediaBox = MediaBox::create([
            'user_id' => $user->id,
            'directory' => $user->name,
            'max_size' => $maxSize,
        ]);

        // 評価
        $this->assertDatabaseHas('media_boxes', [
            'id' => $mediaBox->id,
            'user_id' => $user->id,
            'directory' => $user->name,
            'max_size' => $maxSize,
        ]);
    }

    /**
     * メディアボックス作成
     * 
     * - ディアボックスは、ユーザID単位に１つのみ作成可能であることを確認します。
     * 
     * @link https://github.com/ryossi/feeldee-tracking/wiki/メディアボックス#メディアボックス作成
     */
    public function test_mediaBox_create_exists()
    {
        // 準備
        $user = new class extends User {
            public function getIdAttribute()
            {
                return 1;
            }
            public function getNameAttribute()
            {
                return 'test_user';
            }
        };
        $this->actingAs($user);
        MediaBox::factory()->create([
            'user_id' => $user->id,
        ]);

        // 実行
        $this->assertThrows(function () use ($user) {
            MediaBox::create([
                'user_id' => $user->id,
            ]);
        }, ApplicationException::class, 'MediaBoxExists');
    }

    /**
     * メディアボックス作成
     * 
     * - メディアボックスルートディレクトリが省略可能であることを確認します。
     * - メディアボックス最大容量が省略可能であることを確認します。
     * - メディアボックスルートディレクトリを省略した場合は、メディアボックス所有ユーザIDのMD5ハッシュ値が自動的に設定されることを確認します。
     * 
     * @link https://github.com/ryossi/feeldee-tracking/wiki/メディアボックス#メディアボックス作成
     */
    public function test_mediaBox_create_omit_directory_and_max_size()
    {
        // 準備
        $user = new class extends User {
            public function getIdAttribute()
            {
                return 1;
            }
            public function getNameAttribute()
            {
                return 'test_user';
            }
        };
        $this->actingAs($user);

        // 実行
        $mediaBox = MediaBox::create([
            'user_id' => $user->id,
        ]);

        // 評価
        $this->assertDatabaseHas('media_boxes', [
            'id' => $mediaBox->id,
            'user_id' => $user->id,
            'directory' => md5($user->id),
            'max_size' => null,
        ]);
    }

    /**
     * ユーザEloquentモデルへのメディアボックス関連付け
     * 
     * - HasMediaBoxトレイトを実装することで、プログラムからユーザが所有するメディアボックスに直接アクセスできるようになることを確認します。
     * 
     * @link https://github.com/ryossi/feeldee-tracking/wiki/メディアボックス#ユーザEloquentモデルへのメディアボックス関連付け
     */
    public function test_mediaBox_user_model()
    {
        // 準備
        $user = new class extends User {

            use \Feeldee\MediaBox\Models\HasMediaBox;

            public function getIdAttribute()
            {
                return 1;
            }
            public function getNameAttribute()
            {
                return 'test_user';
            }
        };
        $this->actingAs($user);
        $mediaBox = MediaBox::factory()->create([
            'user_id' => $user->id,
        ]);

        // 実行
        $mediaBoxFromUser = $user->mediaBox;

        // 評価
        $this->assertEquals($mediaBox->id, $mediaBoxFromUser->id, 'プログラムからユーザが所有するメディアボックスに直接アクセスできるようになること');
    }
}
