<?php

namespace Database\Seeders;

use App\Models\News;
use App\Models\NewsCategory;
use App\Models\Order;
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
        Order::factory()
            ->count(30)
            ->create();

//        $categories = NewsCategory::factory()->count(5)->create();
//
//        News::factory()->count(100)->make()->each(function ($news) use ($categories) {
//            $news->category_id = $categories->random()->id;
//            $news->save();
//        });
//
//        DB::update("
//            UPDATE news
//            SET link =
//              CONCAT(
//                'https://placehold.co/640x480/',
//                SUBSTRING(
//                  link,
//                  LOCATE('.png/', link) + 5,
//                  LOCATE('?', link) - (LOCATE('.png/', link) + 5)
//                ),
//                '/000000',
//                SUBSTRING(link, LOCATE('?', link))
//              )
//            WHERE link LIKE 'https://via.placeholder.com/640x480.png/%'
//        ");

        DB::update("
            UPDATE orders
            SET user_avatar =
              CONCAT(
                'https://placehold.co/100x100/',
                SUBSTRING(
                  user_avatar,
                  LOCATE('.png/', user_avatar) + 5,
                  LOCATE('?', user_avatar) - (LOCATE('.png/', user_avatar) + 5)
                ),
                '/000000',
                SUBSTRING(user_avatar, LOCATE('?', user_avatar))
              )
            WHERE user_avatar LIKE 'https://via.placeholder.com/100x100.png/%'
        ");
    }
}
