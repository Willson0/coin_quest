<?php

namespace App\Http\Controllers;

use App\Models\Currency;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class TradeController extends Controller
{
    public function trade (Request $request) {
        $marketed = Currency::where('coingeckoId', $request->marketed)->first();
        $purchasable = Currency::where('coingeckoId', $request->purchasable)->first();

        if (!$marketed || !$purchasable) abort (404, "Криптовалюта не существует или удалена");
        if ($request->marketed == $request->purchasable) abort (404, "Нельзя покупать и продавать одну и ту же криптовалюту");
        if ($request->marketed_count <= 0) abort (404, "Нельзя купить или продать 0 или меньше");

        $user = User::where("telegram_id", $request["initData"]["user"]["id"])->firstOrFail();
        $user->crypto = json_decode($user->crypto, true);
        if (!isset($user->crypto[$request->marketed]) || $user->crypto[$request->marketed] < $request->marketed_count) abort (404, "Недостаточно средств для покупки");

        $user->currencies = Currency::all();
        try {
            $response = Http::get('https://api.coingecko.com/api/v3/coins/markets', [
                'vs_currency' => "usd",
                'ids' => implode(',', array_column($user->currencies->toArray(), 'coingeckoId')),
            ]);
            if ($response->status() === 429) throw new \Exception('Too many requests');
            Cache::put('currenciesData', $response->json(), now()->addMinutes(5));

            $user->currenciesData = $response->json();
        } catch (\Exception $e) {
            if (Cache::has('currenciesData')) $user->currenciesData = Cache::get('currenciesData');
            else $user->currenciesData = null;
        }
        if ($user->currenciesData) {
            $user->currenciesData = array_map(function ($coin) {
                return [
                    'name'        => $coin['name'] ?? null,
                    'symbol'      => strtoupper($coin['symbol'] ?? ''),
                    'logo'        => $coin['image'] ?? null,
                    'price'       => $coin['current_price'] ?? null,
                    'coingeckoId' => $coin['id'] ?? null,
                ];
            }, $user->currenciesData);
        }

        $marketedPrice = collect($user->currenciesData)->first(function ($coin) use ($request) {
            return $coin["coingeckoId"] == $request->marketed;
        })["price"];
        $purchasablePrice = collect($user->currenciesData)->first(function ($coin) use ($request) {
            return $coin["coingeckoId"] == $request->purchasable;
        })["price"];

        $purchasable_count = ($marketedPrice / $purchasablePrice) * $request->marketed_count;

        $crypto = $user->crypto;
        $crypto[$marketed->coingeckoId] -= $request->marketed_count;

        if (!isset($crypto[$purchasable->coingeckoId])) $crypto[$purchasable->coingeckoId] = 0;
        $crypto[$purchasable->coingeckoId] += $purchasable_count;

        User::where("id", $user->id)->update(["crypto" => $crypto]);

        return response()->json(["crypto" => $crypto]);
    }

    public function send (Request $request) {
        if (!$request->has("currency") || !$request->has("amount")) abort (404, "Недостаточно данных для отправки");
        $user = User::where("telegram_id", $request["initData"]["user"]["id"])->firstOrFail();
        $user->crypto = json_decode($user->crypto, true);

        if (!isset($user->crypto[$request->currency]) || $user->crypto[$request->currency] < $request->amount)
            abort (404, "Недостаточно средств для отправки");

        $crypto = $user->crypto;
        $crypto[$request->currency] -= $request->amount;
        $user->crypto = $crypto;

        $user->save();
        return response()->json(["crypto" => $user->crypto]);
    }
}
