<?php

namespace Feeldee\MediaBox\Database\Factories;

use Feeldee\MediaBox\Models\MediaBox;
use Illuminate\Database\Eloquent\Factories\Factory;

class MediaBoxFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = MediaBox::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => $this->faker->randomNumber(),
            'directory' => $this->faker->word(),
        ];
    }
}
