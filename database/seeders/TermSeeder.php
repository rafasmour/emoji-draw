<?php

namespace Database\Seeders;

use App\Models\Term;
use Illuminate\Database\Seeder;

class TermSeeder extends Seeder
{
    private array $terms = [
        // Easy single words
        ['value' => 'cat', 'difficulty' => 'easy', 'category' => 'animals'],
        ['value' => 'dog', 'difficulty' => 'easy', 'category' => 'animals'],
        ['value' => 'fish', 'difficulty' => 'easy', 'category' => 'animals'],
        ['value' => 'bird', 'difficulty' => 'easy', 'category' => 'animals'],
        ['value' => 'elephant', 'difficulty' => 'medium', 'category' => 'animals'],
        ['value' => 'penguin', 'difficulty' => 'medium', 'category' => 'animals'],
        ['value' => 'giraffe', 'difficulty' => 'medium', 'category' => 'animals'],
        ['value' => 'octopus', 'difficulty' => 'medium', 'category' => 'animals'],
        ['value' => 'butterfly', 'difficulty' => 'medium', 'category' => 'animals'],
        ['value' => 'dragon', 'difficulty' => 'hard', 'category' => 'animals'],
        ['value' => 'mermaid', 'difficulty' => 'hard', 'category' => 'misc'],
        ['value' => 'apple', 'difficulty' => 'easy', 'category' => 'food'],
        ['value' => 'pizza', 'difficulty' => 'easy', 'category' => 'food'],
        ['value' => 'cake', 'difficulty' => 'easy', 'category' => 'food'],
        ['value' => 'house', 'difficulty' => 'easy', 'category' => 'objects'],
        ['value' => 'tree', 'difficulty' => 'easy', 'category' => 'nature'],
        ['value' => 'sun', 'difficulty' => 'easy', 'category' => 'nature'],
        ['value' => 'moon', 'difficulty' => 'easy', 'category' => 'nature'],
        ['value' => 'star', 'difficulty' => 'easy', 'category' => 'nature'],
        ['value' => 'cloud', 'difficulty' => 'easy', 'category' => 'nature'],
        ['value' => 'flower', 'difficulty' => 'easy', 'category' => 'nature'],
        ['value' => 'rainbow', 'difficulty' => 'easy', 'category' => 'nature'],
        ['value' => 'volcano', 'difficulty' => 'medium', 'category' => 'nature'],
        ['value' => 'waterfall', 'difficulty' => 'medium', 'category' => 'nature'],
        ['value' => 'car', 'difficulty' => 'easy', 'category' => 'objects'],
        ['value' => 'boat', 'difficulty' => 'easy', 'category' => 'objects'],
        ['value' => 'hat', 'difficulty' => 'easy', 'category' => 'objects'],
        ['value' => 'book', 'difficulty' => 'easy', 'category' => 'objects'],
        ['value' => 'candle', 'difficulty' => 'easy', 'category' => 'objects'],
        ['value' => 'bicycle', 'difficulty' => 'medium', 'category' => 'objects'],
        ['value' => 'umbrella', 'difficulty' => 'medium', 'category' => 'objects'],
        ['value' => 'lighthouse', 'difficulty' => 'medium', 'category' => 'objects'],
        ['value' => 'submarine', 'difficulty' => 'medium', 'category' => 'objects'],
        ['value' => 'telescope', 'difficulty' => 'medium', 'category' => 'objects'],
        ['value' => 'snowman', 'difficulty' => 'easy', 'category' => 'misc'],
        // Phrases
        ['value' => 'fire truck', 'difficulty' => 'easy', 'category' => 'objects'],
        ['value' => 'birthday cake', 'difficulty' => 'easy', 'category' => 'food'],
        ['value' => 'ice cream', 'difficulty' => 'easy', 'category' => 'food'],
        ['value' => 'hot dog', 'difficulty' => 'easy', 'category' => 'food'],
        ['value' => 'shooting star', 'difficulty' => 'easy', 'category' => 'nature'],
        ['value' => 'haunted house', 'difficulty' => 'medium', 'category' => 'misc'],
        ['value' => 'pirate ship', 'difficulty' => 'medium', 'category' => 'misc'],
        ['value' => 'treasure chest', 'difficulty' => 'medium', 'category' => 'misc'],
        ['value' => 'magic wand', 'difficulty' => 'medium', 'category' => 'misc'],
        ['value' => 'hot air balloon', 'difficulty' => 'medium', 'category' => 'objects'],
        ['value' => 'roller coaster', 'difficulty' => 'hard', 'category' => 'objects'],
        ['value' => 'eiffel tower', 'difficulty' => 'medium', 'category' => 'landmarks'],
        ['value' => 'statue of liberty', 'difficulty' => 'hard', 'category' => 'landmarks'],
        ['value' => 'great wall', 'difficulty' => 'hard', 'category' => 'landmarks'],
        ['value' => 'solar system', 'difficulty' => 'hard', 'category' => 'nature'],
    ];

    public function run(): void
    {
        foreach ($this->terms as $term) {
            Term::create([
                'value' => $term['value'],
                'difficulty' => $term['difficulty'],
                'category' => $term['category'],
                'language' => 'en',
            ]);
        }
    }
}
