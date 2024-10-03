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
        $categories = Category::factory()->count(10)->create();

        // Verknüpfe Posts mit zufälligen Kategorien
        $posts->each(function ($post) use ($categories) {
            // Weist jedem Post eine zufällige Anzahl von Kategorien zu
            $post->categories()->attach(
                $categories->random(rand(1, 3))->pluck('id')->toArray()
            );
        });

        // Optionale Benutzererstellung (auskommentiert)
        /* 
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]); 
        */
    }
}
