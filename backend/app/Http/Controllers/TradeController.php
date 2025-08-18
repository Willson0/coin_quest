<?php

namespace App\Http\Controllers;

use App\Http\Requests\TradeBuyCardRequest;
use App\Http\Requests\TradeSendRequest;
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

    public function send (TradeSendRequest $request) {
        $user = User::where("telegram_id", $request["initData"]["user"]["id"])->firstOrFail();
        $user->crypto = json_decode($user->crypto, true);

        if ($request->isTelegram) $isTelegram = true;
        else $isTelegram = false;

        if ($isTelegram) {
            $recipient = User::where("username", $request->wallet)->orWhere("telegram_id", $request->wallet)->first();
            if (!$recipient) abort (404, "Пользователь с таким юзернеймом не зарегистрирован");
        } else {
            $recipient = User::where("wallet", $request->wallet)->first();
            if (!$recipient) abort (404, "Пользователя с таким кошельком не существует");
        }
        if ($recipient->id == $user->id) abort (404, "Нельзя отправить деньги самому себе");

        if (!isset($user->crypto[$request->currency]) || $user->crypto[$request->currency] < $request->amount)
            abort (404, "Недостаточно средств для отправки");

        $crypto = $user->crypto;
        $crypto[$request->currency] -= $request->amount;
        $user->crypto = $crypto;

        $recipient_crypto = json_decode($recipient->crypto, true);
        if (!isset($recipient_crypto[$request->currency])) $recipient_crypto[$request->currency] = 0;
        $recipient_crypto[$request->currency] += $request->amount;
        $recipient->crypto = $recipient_crypto;

        $user->save();
        $recipient->save();
        return response()->json(["crypto" => $user->crypto]);
    }

    public function buyCard (TradeBuyCardRequest $request) {
        $user = User::where("telegram_id", $request["initData"]["user"]["id"])->firstOrFail();

        $crypto = json_decode($user->crypto, true);
        if (!isset($crypto[$request->currency])) $crypto[$request->currency] = 0;
        $crypto[$request->currency] += $request->amount;
        $user->crypto = $crypto;

        $user->save();

        return response()->json(["crypto" => $user->crypto]);
    }
}
