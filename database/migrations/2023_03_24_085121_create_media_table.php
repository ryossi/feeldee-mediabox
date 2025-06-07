<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('media_box_id')->comment('メディアボックスID')->constrained()->cascadeOnDelete();
            $table->string('subdirectory')->nullable()->comment('メディアサブディレクトリ');
            $table->string('filename')->comment('メディアファイル名');
            $table->integer('size')->default(0)->comment('メディアコンテンツサイズ');
            $table->integer('width')->nullable()->comment('メディアコンテンツ幅');
            $table->integer('height')->nullable()->comment('メディアコンテンツ高さ');
            $table->string('content_type', 255)->comment('メディアコンテンツタイプ');
            $table->string('uri')->nullable()->unique()->comment('メディアコンテンツURI');
            $table->dateTime('uploaded_at')->comment('メディアコンテンツアップロード日時');
            $table->bigInteger('created_by')->comment('登録者');
            $table->bigInteger('updated_by')->comment('更新者');
            $table->timestamps();

            $table->unique(['media_box_id', 'subdirectory', 'filename'], 'uk_media');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('media');
    }
};
