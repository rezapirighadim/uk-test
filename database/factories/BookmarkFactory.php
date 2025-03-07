<?php

namespace Database\Factories;

use App\Models\Bookmark;
use Illuminate\Database\Eloquent\Factories\Factory;

class BookmarkFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Bookmark::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'url' => $this->faker->url,
            'title' => $this->faker->optional()->sentence(4),
            'description' => $this->faker->optional()->paragraph,
            'metadata_fetched_at' => $this->faker->optional()->dateTime,
            'fetch_failed' => $this->faker->boolean(20), // 20% chance of failure
            'fetch_error' => function (array $attributes) {
                return $attributes['fetch_failed'] ? $this->faker->sentence : null;
            },
        ];
    }

    /**
     * Indicate that the bookmark has metadata.
     *
     * @return Factory
     */
    public function withMetadata(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'title' => $this->faker->sentence(4),
                'description' => $this->faker->paragraph,
                'metadata_fetched_at' => now(),
                'fetch_failed' => false,
                'fetch_error' => null,
            ];
        });
    }

    /**
     * Indicate that the bookmark is pending metadata fetch.
     *
     * @return Factory
     */
    public function pending(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'title' => null,
                'description' => null,
                'metadata_fetched_at' => null,
                'fetch_failed' => false,
                'fetch_error' => null,
            ];
        });
    }

    /**
     * Indicate that the bookmark metadata fetch has failed.
     *
     * @return Factory
     */
    public function failed(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'title' => null,
                'description' => null,
                'metadata_fetched_at' => null,
                'fetch_failed' => true,
                'fetch_error' => $this->faker->sentence,
            ];
        });
    }
}
