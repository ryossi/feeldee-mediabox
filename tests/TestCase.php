<?php

namespace Tests;

use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use WithWorkbench;

    protected function setUp(): void
    {
        parent::setUp();

        // Factoryのネームスペースを明示的に指定
        \Illuminate\Database\Eloquent\Factories\Factory::guessFactoryNamesUsing(
            fn(string $modelName) => 'Feeldee\\MediaBox\\Database\\Factories\\' . class_basename($modelName) . 'Factory'
        );
    }
}
