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
        $currencies = [
            [
                'tvSymbol' => 'BINANCE:BTCUSDT',
                'coingeckoId' => 'bitcoin',
            ],
            [
                'tvSymbol' => 'BINANCE:ETHUSDT',
                'coingeckoId' => 'ethereum',
            ],
            [
                'tvSymbol' => 'BINANCE:BNBUSDT',
                'coingeckoId' => 'binancecoin',
            ],
            [
                'tvSymbol' => 'BINANCE:XRPUSDT',
                'coingeckoId' => 'ripple',
            ],
            [
                'tvSymbol' => 'BINANCE:SOLUSDT',
                'coingeckoId' => 'solana',
            ],
            [
                'tvSymbol' => 'BINANCE:ADAUSDT',
                'coingeckoId' => 'cardano',
            ],
            [
                'tvSymbol' => 'BINANCE:DOGEUSDT',
                'coingeckoId' => 'dogecoin',
            ],
            [
                'tvSymbol' => 'BINANCE:TONUSDT',
                'coingeckoId' => 'the-open-network',
            ],
            [
                'tvSymbol' => 'BINANCE:TRXUSDT',
                'coingeckoId' => 'tron',
            ],
            [
                'tvSymbol' => 'BINANCE:AVAXUSDT',
                'coingeckoId' => 'avalanche-2',
            ],
            [
                'tvSymbol' => 'BINANCE:SHIBUSDT',
                'coingeckoId' => 'shiba-inu',
            ],
            [
                'tvSymbol' => 'BINANCE:LINKUSDT',
                'coingeckoId' => 'chainlink',
            ],
            [
                'tvSymbol' => 'BINANCE:DOTUSDT',
                'coingeckoId' => 'polkadot',
            ],
            [
                'tvSymbol' => 'BINANCE:MATICUSDT',
                'coingeckoId' => 'matic-network',
            ],
            [
                'tvSymbol' => 'BINANCE:LTCUSDT',
                'coingeckoId' => 'litecoin',
            ],
        ];

        DB::table('currencies')->insert($currencies);

        $fiatCurrencies = [
            [
                'image'  => 'https://flagcdn.com/us.svg',
                'name'   => 'US Dollar',
                'symbol' => 'USD',
            ],
            [
                'image'  => 'https://flagcdn.com/eu.svg',
                'name'   => 'Euro',
                'symbol' => 'EUR',
            ],
            [
                'image'  => 'https://flagcdn.com/gb.svg',
                'name'   => 'British Pound Sterling',
                'symbol' => 'GBP',
            ],
            [
                'image'  => 'https://flagcdn.com/jp.svg',
                'name'   => 'Japanese Yen',
                'symbol' => 'JPY',
            ],
            [
                'image'  => 'https://flagcdn.com/ch.svg',
                'name'   => 'Swiss Franc',
                'symbol' => 'CHF',
            ],
            [
                'image'  => 'https://flagcdn.com/cn.svg',
                'name'   => 'Chinese Yuan',
                'symbol' => 'CNY',
            ],
            [
                'image'  => 'https://flagcdn.com/ru.svg',
                'name'   => 'Russian Ruble',
                'symbol' => 'RUB',
            ],
            [
                'image'  => 'https://flagcdn.com/in.svg',
                'name'   => 'Indian Rupee',
                'symbol' => 'INR',
            ],
            [
                'image'  => 'https://flagcdn.com/ca.svg',
                'name'   => 'Canadian Dollar',
                'symbol' => 'CAD',
            ],
            [
                'image'  => 'https://flagcdn.com/au.svg',
                'name'   => 'Australian Dollar',
                'symbol' => 'AUD',
            ],
            [
                'image'  => 'https://flagcdn.com/kz.svg',
                'name'   => 'Kazakhstani Tenge',
                'symbol' => 'KZT',
            ],
            [
                'image'  => 'https://flagcdn.com/tr.svg',
                'name'   => 'Turkish Lira',
                'symbol' => 'TRY',
            ],
            [
                'image'  => 'https://flagcdn.com/by.svg',
                'name'   => 'Belarusian Ruble',
                'symbol' => 'BYN',
            ],
            [
                'image'  => 'https://flagcdn.com/ua.svg',
                'name'   => 'Ukrainian Hryvnia',
                'symbol' => 'UAH',
            ],
            [
                'image'  => 'https://flagcdn.com/il.svg',
                'name'   => 'Israeli Shekel',
                'symbol' => 'ILS',
            ],
        ];

        DB::table('fiat_currencies')->insert($fiatCurrencies);


        Order::factory()
            ->count(30)
            ->create();

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
