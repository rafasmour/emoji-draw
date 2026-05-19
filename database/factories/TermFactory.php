<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Term>
 */
class TermFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'value' => fake()->randomElement([
                'cat', 'dog', 'fish', 'elephant', 'penguin', 'butterfly',
                'pizza', 'cake', 'ice cream', 'hot dog',
                'house', 'bicycle', 'umbrella', 'lighthouse', 'candle',
                'rainbow', 'volcano', 'waterfall', 'snowman',
                'fire truck', 'pirate ship', 'treasure chest', 'haunted house', 'hot air balloon',
            ]),
            'difficulty' => fake()->randomElement(['easy', 'medium', 'hard']),
            'category' => fake()->randomElement(['animals', 'food', 'objects', 'nature', 'landmarks', 'misc']),
            'language' => 'en',
        ];
    }
}
