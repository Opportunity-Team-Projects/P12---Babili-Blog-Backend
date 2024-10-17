<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Post;
use App\Models\Category;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Erstellt 10 User, 50 Posts und 10 Kategorien
        User::factory(10)->create();
        $posts = Post::factory()->count(50)->create();

        $categoriesData = [
            ['categoryName' => 'Technology'],
            ['categoryName' => 'Gaming'],
            ['categoryName' => 'Hardware'],
            ['categoryName' => 'Software'],
            ['categoryName' => 'Cybersecurity'],
            ['categoryName' => 'Innovations'],
            ['categoryName' => 'Education']
        ];

        // Speichere Kategorien in der Datenbank und hole die Collection zurück
        $categories = collect();
        foreach ($categoriesData as $category) {
            $categories->push(Category::create($category));
        }

        // Weist jedem Post eine zufällige Anzahl von Kategorien zu
        $posts->each(function ($post) use ($categories) {
            $post->categories()->attach(
                $categories->random(rand(1, 3))->pluck('id')->toArray()
            );
        });
    }
}
