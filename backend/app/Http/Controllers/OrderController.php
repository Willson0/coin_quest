<?php

namespace App\Http\Controllers;

use App\Models\Currency;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index (Request $request) {
        $user = User::where("telegram_id", $request["initData"]["user"]["id"])->firstOrFail();
        return response()->json(Order::all());
    }

    public function buy (Order $order, Request $request) {
        $user = User::where("telegram_id", $request["initData"]["user"]["id"])->firstOrFail();

        $fiat = $request->count * $order->price;
        if ($order->min_limit && $fiat < $order->min_limit) abort (400, "Минимальный лимит: " . $order->min_limit);
        if ($order->max_limit && $fiat > $order->max_limit) abort (400, "Максимальный лимит: " . $order->max_limit);

        if ($order->remain < $request->count) abort (400, "У ордера недостаточно криптовалюты");

        $currency = Currency::find($order->currency_id);
        if (!$currency) {
            $order->delete();
            abort (400, "Неизвестная криптовалюта");
        }

        $crypto = json_decode($user->crypto, true);
        if (!isset($crypto[$currency->coingeckoId])) $crypto[$currency->coingeckoId] = 0;
        $crypto[$currency->coingeckoId] += $request->count;

        $user->crypto = json_encode($crypto);
        $user->save();

        $order->remain = $order->remain - $request->count;
        if ((float)$order->remain < 1e-15) $order->delete();
        else {
            $order->count_deals += 1;
            $order->save();
        }

        return response()->json([
            "crypto" => json_decode($user->crypto, 1),
            "orders" => $this->index($request)->original
        ]);
    }
}
