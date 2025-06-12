<?php

namespace Database\Factories;

use App\Models\Comment;
use App\Models\User;
use App\Models\Video;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Comment>
 */
class CommentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'content' => $this->faker->paragraph(),
            'commentable_type' => Video::class,
            'commentable_id' => Video::factory(),
            'parent_id' => null,
        ];
    }

    /**
     * Create a reply comment.
     */
    public function reply($parentId = null)
    {
        return $this->state(function (array $attributes) use ($parentId) {
            return [
                'parent_id' => $parentId ?? Comment::factory(),
            ];
        });
    }
}
