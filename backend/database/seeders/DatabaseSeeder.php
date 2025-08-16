<?php

namespace Database\Seeders;

use App\Models\News;
use App\Models\NewsCategory;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $categories = NewsCategory::factory()->count(5)->create();

        News::factory()->count(100)->make()->each(function ($news) use ($categories) {
            $news->category_id = $categories->random()->id;
            $news->save();
        });

        DB::update("
            UPDATE news
            SET link =
              CONCAT(
                'https://placehold.co/640x480/',
                SUBSTRING(
                  link,
                  LOCATE('.png/', link) + 5,
                  LOCATE('?', link) - (LOCATE('.png/', link) + 5)
                ),
                '/000000',
                SUBSTRING(link, LOCATE('?', link))
              )
            WHERE link LIKE 'https://via.placeholder.com/640x480.png/%'
        ");
    }
}
