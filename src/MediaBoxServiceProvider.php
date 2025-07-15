<?php

namespace Feeldee\MediaBox;

use Illuminate\Foundation\AliasLoader;
use Illuminate\Foundation\Console\AboutCommand;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;

class MediaBoxServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/mediabox.php',
            'mediabox'
        );

        // HTMLキャストフック登録
        Config::set(
            'feeldee.html_cast_hooks',
            array_merge(
                Config::get('feeldee.html_cast_hooks', []),
                [\Feeldee\MediaBox\Hooks\HTML::class]
            )
        );
        // URLキャストフック登録
        Config::set(
            'feeldee.url_cast_hooks',
            array_merge(
                Config::get('feeldee.url_cast_hooks', []),
                [\Feeldee\MediaBox\Hooks\URL::class]
            )
        );

        // ファサードのエイリアスを登録 
        AliasLoader::getInstance()->alias(
            'Image',
            \Feeldee\MediaBox\Facades\Image::class
        );
        AliasLoader::getInstance()->alias(
            'MediaBox',
            \Feeldee\MediaBox\Facades\MediaBox::class
        );
    }

    /**
     * All of the container bindings that should be registered.
     *
     * @var array
     */
    public $bindings = [];

    /**
     * All of the container singletons that should be registered.
     *
     * @var array
     */
    public $singletons = [];

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // 追加言語ファイル
        $this->loadTranslationsFrom(__DIR__ . '/../lang', 'feeldee');
        $this->publishes([
            __DIR__ . '/../lang' => $this->app->langPath('vendor/feeldee'),
        ]);

        // 追加設定
        $this->publishes([
            __DIR__ . '/../config/mediabox.php' => config_path('mediabox.php'),
        ]);

        // 追加マイグレーション
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // 追加情報
        AboutCommand::add('Feeldee', fn() => ['MediaBox Version' => '1.0.0']);
    }
}
