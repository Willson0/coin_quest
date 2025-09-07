<?php

namespace App\Http\Controllers;

use App\Models\Achievements;
use App\Models\Course;
use App\Models\Currency;
use App\Models\FiatCurrency;
use App\Models\Lesson;
use App\Models\News;
use App\Models\NewsCategory;
use App\Models\Tournament;
use App\Models\User;
use App\Models\UserLesson;
use App\Models\WhiteList;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Laravel\Facades\Telegram;

class AuthController extends Controller
{
    public function profile (Request $request) {
        $user = User::where("telegram_id", $request["initData"]["user"]["id"])->first();

        if (!$user) {
            $alphabet = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';
            $address = 'D';
            for ($i = 0; $i < 33; $i++) $address .= $alphabet[random_int(0, strlen($alphabet)-1)];

            $user = User::create([
                "telegram_id" => $request["initData"]["user"]["id"],
                "username" => $request["initData"]["user"]["username"] ?? "",
                "fullname" => $request["initData"]["user"]["first_name"]
                    ?? $request["initData"]["user"]["last_name"]
                        ?? $request["initData"]["user"]["username"],
                "avatar" => $request["initData"]["user"]["photo_url"],
                "wallet_private" => bin2hex(random_bytes(32)),
                "wallet" => $address,
            ]);
        } else {
            $user->update([
                "fullname" => $request["initData"]["user"]["first_name"]
                    ?? $request["initData"]["user"]["last_name"]
                        ?? $request["initData"]["user"]["username"],
                "username" => $request["initData"]["user"]["username"] ?? "",
                "avatar" => $request["initData"]["user"]["photo_url"]
            ]);
        }

        if (!WhiteList::where("value", $user->telegram_id)
            ->orWhere("value", $user->username)->exists()) abort(423, "Not whitelisted");

        $news = NewsCategory::all();
        foreach ($news as &$item) {
            $item->news = News::where("category_id", $item->id)->orderBy('id', 'desc')->limit(4)->get();
        }
        $user->news = $news;
        $user->allNews = News::limit(10)->orderBy('id', 'desc')->get();

        $user->levels = json_decode(env("LEVELS"), true);

        $user->courses = Course::all();
        foreach ($user->courses as &$course) {
            $course->lessons = Lesson::where("course_id", $course->id)->
                leftJoin('user_lessons', function($join) use ($user) {
                    $join->on('lessons.id', '=', 'user_lessons.lesson_id')
                        ->where('user_lessons.user_id', '=', $user->id);
                })
                ->select('lessons.id', 'lessons.title', 'lessons.description', "lessons.count_tries", 'lessons.number', 'lessons.course_id', 'user_lessons.id as user_lesson_id', 'user_lessons.points as user_points')
                ->orderBy('lessons.number')
                ->get()
                ->groupBy('id')
                ->map(function($items) {
                    $last = $items->whereNotNull('user_lesson_id')->sortByDesc('user_lesson_id')->first() ?? $items->first();
                    $last->user_count_tries = $items->whereNotNull('user_lesson_id')->count();
                    return $last;
                })
                ->values();
        }

        $user->tournament = Tournament::where("date_start", "<", Carbon::now())->where("date_end", ">", Carbon::now())->first();
        if (!$user->tournament) {
            $user->tournament = Tournament::where("date_end", "<", Carbon::now())->orderByDesc("date_end")->first();
            $user->closest_tournament = Tournament::where("date_start", ">", Carbon::now())->orderBy("date_start")->first();
//            if ($user->closest_tournament->type === "lesson") $user->closest_tournament->object = Course::find($user->closest_tournament->object_id);
        }
        if ($user->tournament) {
            $sql = "
              select
                user_id,
                lesson_id,
                points,
                row_number() over (
                  partition by user_id, lesson_id
                  order by created_at desc, id desc
                ) as rn
              from user_lessons
            ";

            if ($user->tournament->type === 'lesson') {
                $inArray = Lesson::where("course_id", $user->tournament->object_id)->pluck('id')->toArray();
                if (count($inArray) > 0)
                    $sql .= " where lesson_id in (".implode(',', $inArray).")";
            } else if ($user->tournament->type === 'exam') {
                $inArray = Lesson::where("id", $user->tournament->object_id)->pluck('id')->toArray();
                if (count($inArray) > 0)
                    $sql .= " where lesson_id in (".implode(',', $inArray).")";
            }
            else $sql .= " where created_at between '{$user->tournament->date_start}' and '{$user->tournament->date_end}'";

            $result = DB::table(DB::raw("($sql) t"))
                ->join('users as u', 'u.id', '=', 't.user_id')
                ->where('t.rn', 1)
                ->groupBy('u.id', 'u.fullname')
                ->selectRaw('u.id as user_id, u.fullname as name, u.avatar as avatar, SUM(t.points) as points')
                ->orderByDesc('points')
                ->get()
                ->map(fn ($r) => [
                    'user_id' => (int)$r->user_id,
                    'name'    => $r->name,
                    'avatar'  => $r->avatar,
                    'points'  => (int)$r->points,
                ])
                ->toArray();
            $user->tournament->top = $result;
        }

        $user->fiat_currencies = FiatCurrency::all();
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
                    "change" => $coin["price_change_percentage_24h"] ?? null
                ];
            }, $user->currenciesData);
        }
        $user->crypto = json_decode($user->crypto, true);
        $user->achievements = Achievements::all();

        $user->pinned_achievements = json_decode($user->pinned_achievements, true);

        $inChannels = [];
        $chats = Achievements::where("type", "channel")->pluck("progress")->toArray();
        foreach ($chats as $chat_id) {
            try {
                $result = Telegram::getChatMember([
                    'chat_id' => $chat_id,
                    'user_id' => $user->telegram_id,
                ]);
                Log::critical($result);

                $status = $result->get('status');
                if (in_array($status, ['creator', 'administrator', 'member']))
                    $inChannels[] = $chat_id;
            } catch (\Telegram\Bot\Exceptions\TelegramResponseException $e) {}
        }
        $user->channels = $inChannels;

        $achievementsTournaments = Achievements::where("type", "tournament")->pluck("progress")->toArray();
        $achievementsTournaments = Tournament::whereIn("id", $achievementsTournaments)
            ->where("date_end", "<", Carbon::now())->get();
        $winners = Cache::get('tournament_winners', []);
        foreach ($achievementsTournaments as $tournament) {
            if (isset($winners[$tournament->id]) && $winners[$tournament->id]) continue;
            try {
                $sql = "
                    select
                        user_id,
                        lesson_id,
                        points,
                        row_number() over (
                            partition by user_id, lesson_id
                            order by created_at desc, id desc
                        ) as rn
                    from user_lessons
                ";

                if ($tournament->type === 'lesson') {
                    $inArray = Lesson::where("course_id", $user->tournament->object_id)->pluck('id')->toArray();
                    if ($inArray)
                        $sql .= " where lesson_id in (" . implode(',', $inArray) . ")";
                } else if ($tournament->type === 'exam') {
                    $inArray = Lesson::where("id", $user->tournament->object_id)->pluck('id')->toArray();
                    if ($inArray)
                        $sql .= " where lesson_id in (" . implode(',', $inArray) . ")";
                } else {
                    $sql .= " where created_at between '{$user->tournament->date_start}' and '{$user->tournament->date_end}'";
                }

                $firstUserId = DB::table(DB::raw("($sql) t"))
                    ->join('users as u', 'u.id', '=', 't.user_id')
                    ->where('t.rn', 1)
                    ->selectRaw('u.id as user_id, SUM(t.points) as points')
                    ->groupBy('u.id')
                    ->orderByDesc('points')
                    ->limit(1)
                    ->value('user_id');

                $winners["$tournament->id"] = $firstUserId;
            } catch (\Exception $e) {}
        }
        Cache::forever('tournament_winners', $winners);
        $userWinners = array_filter($winners, fn($winnerId) => $winnerId == $user->id);
        $user->wonTournaments = array_keys($userWinners);

        return response()->json($user);
    }
}
