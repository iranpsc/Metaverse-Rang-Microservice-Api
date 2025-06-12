<?php

namespace Database\Factories;

use App\Models\Video;
use App\Models\VideoSubCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Video>
 */
class VideoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $title = $this->faker->sentence();

        return [
            'title' => $title,
            'slug' => Str::slug($title),
            'description' => $this->faker->paragraph(),
            'fileName' => $this->faker->word() . '.mp4',
            'image' => $this->faker->word() . '.jpg',
            'video_sub_category_id' => VideoSubCategory::factory(),
            'creator_code' => 'hm-' . $this->faker->numberBetween(10000, 99999),
            'visits' => $this->faker->numberBetween(0, 1000),
        ];
    }
}
