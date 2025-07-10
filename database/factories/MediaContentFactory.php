<?php

namespace Feeldee\MediaBox\Database\Factories;

use Feeldee\MediaBox\Models\MediaContent;
use Illuminate\Database\Eloquent\Factories\Factory;

class MediaContentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = MediaContent::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'filename' => $this->faker->word() . '.' . $this->faker->fileExtension(),
            'size' => $this->faker->numberBetween(1000, 1000000),
            'content_type' => $this->faker->mimeType(),
            'uri' => $this->faker->url(),
            'uploaded_at' => $this->faker->dateTimeThisYear(),
        ];
    }
}
