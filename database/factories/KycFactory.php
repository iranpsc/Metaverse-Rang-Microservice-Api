<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Kyc>
 */
class KycFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'melli_card' => $this->faker->word,
            'fname' => $this->faker->word,
            'lname' => $this->faker->word,
            'melli_code' => $this->faker->word,
            'province' => $this->faker->word,
            'status' => $this->faker->randomNumber(0),
            'user_id' => \App\Models\User::factory(),
            'errors' => $this->faker->words(3, true),
            'verify_text' => $this->faker->text,
            'video' => 'https://www.youtube.com/watch?v=6v2L2UGZJAM',
        ];
    }
}
