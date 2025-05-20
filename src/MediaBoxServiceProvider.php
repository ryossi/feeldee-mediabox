<?php

namespace Feeldee\MediaBox;

use Illuminate\Foundation\Console\AboutCommand;
use Illuminate\Support\ServiceProvider;

class MediaBoxServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/feeldee.php',
            'feeldee'
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
        // 追加設定
        $this->publishes([
            __DIR__ . '/../config/feeldee.php' => config_path('feeldee.php'),
        ]);

        // 追加マイグレーション
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // 追加情報
        AboutCommand::add('Feeldee', fn() => ['MediaBox Version' => '1.0.0']);
    }
}
