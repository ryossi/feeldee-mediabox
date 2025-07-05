<?php

namespace Tests\Feature;

use Feeldee\Framework\Exceptions\ApplicationException;
use Feeldee\MediaBox\Models\MediaBox;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Tests\Models\User;

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

    /**
     * コンテンツアップロード
     * 
     * - コンテンツをメディアボックスディスクにアップロードできることを確認します。
     * - ローカルファイルパスを指定してアップロードできることを確認します。
     * - メディアコンテンツサイズがアップロードコンテンツから自動的に計算されることを確認します。
     * - メディアコンテンツ幅がアップロードコンテンツから自動的に計算されることを確認します。
     * - メディアコンテンツ高さがアップロードコンテンツから自動的に計算されることを確認します。
     * - メディアコンテンツタイプがアップロードコンテンツから自動的に計算されることを確認します。
     * - メディアファイル名を指定した場合は、指定した値がメディアコンテンツファイル名として設定されることを確認します。
     * - メディアディレクトリを指定した場合は、指定した値がメディアコンテンツサブディレクトリとして設定されることを確認します。
     * 
     * @link https://github.com/ryossi/feeldee-tracking/wiki/メディアボックス#コンテンツアップロード
     */
    public function test_mediaBox_upload_filepath()
    {
        // 準備
        Storage::fake();
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
        $mediaBox = MediaBox::factory()->create([
            'user_id' => $user->id,
        ]);
        $filePath = __DIR__ . '/test_files/test_image.jpg'; // テスト用の画像ファイルパス
        $filename = 'test.jpg';
        $subdirectory = 'uploads';

        // 実行
        $medium = $mediaBox->upload($filePath, $filename, $subdirectory);

        // 評価
        $size = Storage::disk()->size($medium->path); // メディアコンテンツサイズ
        $width = 120; // メディアコンテンツ幅
        $height = 120; // メディアコンテンツ高さ
        $content_type = 'image/jpeg'; // メディアコンテンツタイプ

        // コンテンツをメディアボックスディスクにアップロードできること
        $this->assertDatabaseHas('media', [
            'id' => $medium->id,
            'media_box_id' => $mediaBox->id,
            'filename' => $filename,
            'size' => $size,
            'width' => $width,
            'height' => $height,
            'content_type' => $content_type,
            'subdirectory' => $subdirectory,
        ]);
        $this->assertTrue(Storage::disk()->exists($medium->path), 'ローカルファイルパスを指定してアップロードできること');
        $this->assertEquals($size, $medium->size, 'メディアコンテンツサイズがアップロードコンテンツから自動的に計算されること');
        $this->assertEquals($width, $medium->width, 'メディアコンテンツ幅がアップロードコンテンツから自動的に計算されること');
        $this->assertEquals($height, $medium->height, 'メディアコンテンツ高さがアップロードコンテンツから自動的に計算されること');
        $this->assertEquals($content_type, $medium->content_type, 'メディアコンテンツタイプがアップロードコンテンツから自動的に計算されること');
        $this->assertEquals($filename, $medium->filename, 'メディアファイル名を指定した場合は、指定した値がメディアコンテンツファイル名として設定されること');
        $this->assertEquals($subdirectory, $medium->subdirectory, 'メディアディレクトリを指定した場合は、指定した値がメディアコンテンツサブディレクトリとして設定されること');
    }

    /**
     * コンテンツアップロード
     * 
     * - コンテンツをメディアボックスディスクにアップロードできることを確認します。
     * - Base64形式コンテンツ文字列をアップロードできることを確認します。
     * - メディアコンテンツサイズがアップロードコンテンツから自動的に計算されることを確認します。
     * - メディアコンテンツ幅がアップロードコンテンツから自動的に計算されることを確認します。
     * - メディアコンテンツ高さがアップロードコンテンツから自動的に計算されることを確認します。
     * - メディアコンテンツタイプがアップロードコンテンツから自動的に計算されることを確認します。
     * - メディアファイル名を指定した場合は、指定した値がメディアコンテンツファイル名として設定されることを確認します。
     * 
     * @link https://github.com/ryossi/feeldee-tracking/wiki/メディアボックス#コンテンツアップロード
     */
    public function test_mediaBox_upload_base64()
    {
        // 準備
        Storage::fake();
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
        $mediaBox = MediaBox::factory()->create([
            'user_id' => $user->id,
        ]);
        $data = 'data:image/jpeg;base64,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';
        $filename = 'test.jpg';

        // 実行
        $medium = $mediaBox->upload($data, $filename);

        // 評価
        $size = Storage::disk()->size($medium->path); // メディアコンテンツサイズ
        $width = 120; // メディアコンテンツ幅
        $height = 120; // メディアコンテンツ高さ
        $content_type = 'image/jpeg'; // メディアコンテンツタイプ

        // コンテンツをメディアボックスディスクにアップロードできること
        $this->assertDatabaseHas('media', [
            'id' => $medium->id,
            'media_box_id' => $mediaBox->id,
            'filename' => $filename,
            'size' => $size,
            'width' => $width,
            'height' => $height,
            'content_type' => $content_type,
            'subdirectory' => null,
            'filename' => $filename,
        ]);
        $this->assertTrue(Storage::disk()->exists($medium->path), 'Base64形式コンテンツ文字列をアップロードできること');
        $this->assertEquals($size, $medium->size, 'メディアコンテンツサイズがアップロードコンテンツから自動的に計算されること');
        $this->assertEquals($width, $medium->width, 'メディアコンテンツ幅がアップロードコンテンツから自動的に計算されること');
        $this->assertEquals($height, $medium->height, 'メディアコンテンツ高さがアップロードコンテンツから自動的に計算されること');
        $this->assertEquals($content_type, $medium->content_type, 'メディアコンテンツタイプがアップロードコンテンツから自動的に計算されること');
        $this->assertEquals($filename, $medium->filename, 'メディアファイル名を指定した場合は、指定した値がメディアコンテンツファイル名として設定されること');
    }

    /**
     * コンテンツアップロード
     * 
     * - コンテンツをメディアボックスディスクにアップロードできることを確認します。
     * - アップロードファイルをアップロードできることを確認します。
     * - メディアコンテンツサイズがアップロードコンテンツから自動的に計算されることを確認します。
     * - メディアコンテンツ幅がアップロードコンテンツから自動的に計算されることを確認します。
     * - メディアコンテンツ高さがアップロードコンテンツから自動的に計算されることを確認します。
     * - メディアコンテンツタイプがアップロードコンテンツから自動的に計算されることを確認します。
     * - メディアファイル名は、指定したかった場合はメディアコンテンツのオリジナルファイル名となることを確認します。
     * 
     * @link https://github.com/ryossi/feeldee-tracking/wiki/メディアボックス#コンテンツアップロード
     */
    public function test_mediaBox_upload_uploaded()
    {
        // 準備
        Storage::fake();
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
        $mediaBox = MediaBox::factory()->create([
            'user_id' => $user->id,
        ]);
        $filename = 'test.jpg'; // メディアコンテンツファイル名
        $width = 600; // メディアコンテンツ幅
        $height = 400; // メディアコンテンツ高さ
        $content_type = 'image/jpeg'; // メディアコンテンツタイプ
        $file = UploadedFile::fake()->image($filename, $width, $height)->mimeType($content_type);

        // 実行
        $medium = $mediaBox->upload($file);

        // 評価
        $size = Storage::disk()->size($medium->path); // メディアコンテンツサイズ

        // コンテンツをメディアボックスディスクにアップロードできること
        $this->assertDatabaseHas('media', [
            'id' => $medium->id,
            'media_box_id' => $mediaBox->id,
            'filename' => $filename,
            'size' => $size,
            'width' => $width,
            'height' => $height,
            'content_type' => $content_type,
            'subdirectory' => null,
        ]);
        $this->assertTrue(Storage::disk()->exists($medium->path), 'ローカルファイルをアップロードできること');
        $this->assertEquals($size, $medium->size, 'メディアコンテンツサイズがアップロードコンテンツから自動的に計算されること');
        $this->assertEquals($width, $medium->width, 'メディアコンテンツ幅がアップロードコンテンツから自動的に計算されること');
        $this->assertEquals($height, $medium->height, 'メディアコンテンツ高さがアップロードコンテンツから自動的に計算されること');
        $this->assertEquals($content_type, $medium->content_type, 'メディアコンテンツタイプがアップロードコンテンツから自動的に計算されること');
        $this->assertEquals($filename, $medium->filename, 'メディアファイル名は、指定したかった場合はメディアコンテンツのオリジナルファイル名となること');
    }

    /**
     * コンテンツアップロード
     * 
     * - メアップロードの際に、画像の最大幅をコンフィグレーションで制限することができることを確認します。
     * - 画像の最大幅を制限している場合には、リサイズ後の画像のメディアコンテンツサイズが設定されることを確認します。
     * - 画像の最大幅を制限している場合には、リサイズ後の画像のメディアコンテンツ幅が設定されることを確認します。
     * - 画像の最大幅を制限している場合には、リサイズ後の画像のメディアコンテンツ高さが設定されることを確認します。
     * 
     * @link https://github.com/ryossi/feeldee-tracking/wiki/メディアボックス#コンテンツアップロード
     */
    public function test_mediaBox_upload_max_width()
    {
        // 準備
        Storage::fake();
        Config::set(MediaBox::CONFIG_KEY_UPLOAD_IMAGE_MAX_WIDTH, 600); // 画像の最大幅を600pxに設定
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
        $mediaBox = MediaBox::factory()->create([
            'user_id' => $user->id,
        ]);
        $filename = 'test.jpg'; // メディアコンテンツファイル名
        $content_type = 'image/jpeg'; // メディアコンテンツタイプ
        $file = UploadedFile::fake()->image($filename, 1200, 900)->mimeType($content_type);

        // 実行
        $medium = $mediaBox->upload($file);

        // 評価
        $size = Storage::disk()->size($medium->path); // メディアコンテンツサイズ
        $width = 600; // メディアコンテンツ幅
        $height = 450; // メディアコンテンツ高さ

        // コンテンツをメディアボックスディスクにアップロードできること
        $this->assertDatabaseHas('media', [
            'id' => $medium->id,
            'media_box_id' => $mediaBox->id,
            'filename' => $filename,
            'size' => $size,
            'width' => $width,
            'height' => $height,
            'content_type' => $content_type,
            'subdirectory' => null,
        ]);
        $this->assertTrue(Storage::disk()->exists($medium->path), 'ローカルファイルをアップロードできること');
        $this->assertEquals($size, $medium->size, '画像の最大幅を制限している場合には、リサイズ後の画像のメディアコンテンツサイズが設定されること');
        $this->assertEquals($width, $medium->width, '画像の最大幅を制限している場合には、リサイズ後の画像のメディアコンテンツ幅が設定されること');
        $this->assertEquals($height, $medium->height, '画像の最大幅を制限している場合には、リサイズ後の画像のメディアコンテンツ高さが設定されること');
    }

    /**
     * コンテンツアップロード
     * 
     * - メディアボックス使用済サイズとアップロードするコンテンツの合計がメディアボックス最大サイズ以上の場合は、コンテンツアップロードは失敗することを確認します。
     * 
     * @link https://github.com/ryossi/feeldee-tracking/wiki/メディアボックス#コンテンツアップロード
     */
    public function test_mediaBox_upload_exceed_max_size()
    {
        // 準備
        Storage::fake();
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
        $mediaBox = MediaBox::factory()->create([
            'user_id' => $user->id,
            'max_size' => 1, // メディアボックス最大サイズを1MBに設定
        ]);
        $filePath = __DIR__ . '/test_files/test_image_large.jpg'; // テスト用の大きな画像ファイルパス

        // 実行
        $this->assertThrows(function () use ($mediaBox, $filePath) {
            $mediaBox->upload($filePath);
        }, ApplicationException::class, 'MediaBoxNotFreeSpace');
    }

    /**
     * ユーザEloquentモデルへのメディアボックス関連付け
     * 
     * - ユーザがメディアボックスを持っているかどうかについて確認することができることを確認します。
     * 
     * @link https://github.com/ryossi/feeldee-tracking/wiki/メディアボックス#ユーザEloquentモデルへのメディアボックス関連付け
     */
    public function test_user_has_mediaBox_true()
    {
        // 準備
        $user = User::create([
            'name' => 'テストユーザ',
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);
        $this->actingAs($user);
        MediaBox::factory()->create([
            'user_id' => $user->id,
        ]);

        // 実行
        $hasMediaBox = $user->hasMediaBox();

        // 評価
        $this->assertTrue($hasMediaBox, 'ユーザがメディアボックスを持っていることを確認できること');
    }

    /**
     * ユーザEloquentモデルへのメディアボックス関連付け
     * 
     * - ユーザがメディアボックスを持っているかどうかについて確認することができることを確認します。
     * 
     * @link https://github.com/ryossi/feeldee-tracking/wiki/メディアボックス#ユーザEloquentモデルへのメディアボックス関連付け
     */
    public function test_user_has_mediaBox_false()
    {
        // 準備
        $user = User::create([
            'name' => 'テストユーザ',
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);
        $this->actingAs($user);

        // 実行
        $hasMediaBox = $user->hasMediaBox();

        // 評価
        $this->assertFalse($hasMediaBox, 'ユーザがメディアボックスを持っていないことを確認できること');
    }

    /**
     * ユーザEloquentモデルへのメディアボックス関連付け
     * 
     * - コンフィグレーションでメディアボックスとの関連付けタイプを"composition"にすることにより、ユーザEloquentモデルがアプリケーションで削除された場合には、関連付けされた全てのメディアボックスも同時に削除することができることを確認します。
     *
     * @link https://github.com/ryossi/feeldee-tracking/wiki/メディアボックス#ユーザEloquentモデルへのメディアボックス関連付け
     */
    public function test_user_mediaBox_composition()
    {
        // 準備
        config([MediaBox::CONFIG_KEY_USER_RELATION_TYPE => MediaBox::USER_RELATION_TYPE_COMPOSITION]);
        $user = User::create([
            'name' => 'テストユーザ',
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);
        $this->actingAs($user);
        MediaBox::factory()->create([
            'user_id' => $user->id,
        ]);
        $this->assertDatabaseCount('media_boxes', 1);

        // 実行
        $user->delete();

        // 評価
        // ユーザEloquentモデルが削除された場合には、関連付けされた全てのメディアボックスも同時に削除されること
        $this->assertDatabaseCount('media_boxes', 0);
        $this->assertDatabaseCount('users', 0);
    }

    /**
     * ユーザEloquentモデルへのメディアボックス関連付け
     * 
     * - コンフィグレーションでメディアボックスとユーザとの関連付けタイプを"aggregation"にすることにより、ユーザEloquentモデルがアプリケーションで削除された場合には、関連付けされた全てのメディアボックスは削除されないことを確認します。
     *
     * @link https://github.com/ryossi/feeldee-tracking/wiki/メディアボックス#ユーザEloquentモデルへのメディアボックス関連付け
     */
    public function test_user_mediaBox_aggregation()
    {
        // 準備
        config([MediaBox::CONFIG_KEY_USER_RELATION_TYPE => MediaBox::USER_RELATION_TYPE_AGGREGATION]);
        $user = User::create([
            'name' => 'テストユーザ',
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);
        $this->actingAs($user);
        MediaBox::factory()->create([
            'user_id' => $user->id,
        ]);
        $this->assertDatabaseCount('media_boxes', 1);

        // 実行
        $user->delete();

        // 評価
        // ユーザEloquentモデルが削除された場合には、関連付けされた全てのメディアボックスは削除されないこと
        $this->assertDatabaseCount('media_boxes', 1);
        $this->assertDatabaseCount('users', 0);
    }
}
