<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;
use App\Models\Post;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Post>
 */
class PostFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Post::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'content' => fake()->sentence(5),
            'image' => null,
            'location' => fake()->randomElement((array_keys(config('locations.options')))),
            'is_approved' => fake()->boolean(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
