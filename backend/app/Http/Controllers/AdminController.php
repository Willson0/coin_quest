<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdminCreateAchievementRequest;
use App\Http\Requests\AdminCreateCourseRequest;
use App\Http\Requests\AdminCreateCurrencyRequest;
use App\Http\Requests\AdminCreateLessonRequest;
use App\Http\Requests\AdminCreateNewsRequest;
use App\Http\Requests\AdminCreateTournamentRequest;
use App\Http\Requests\AdminUpdateAchievementRequest;
use App\Http\Requests\AdminUpdateCourseRequest;
use App\Http\Requests\AdminUpdateCurrencyRequest;
use App\Http\Requests\adminUpdateNewsCategoryRequest;
use App\Http\Requests\AdminUpdateNewsRequest;
use App\Http\Requests\AdminUpdateTournamentRequest;
use App\Http\utils;
use App\Models\Achievements;
use App\Models\Admin;
use App\Models\AdminCookie;
use App\Models\Course;
use App\Models\Currency;
use App\Models\Lesson;
use App\Models\News;
use App\Models\NewsCategory;
use App\Models\Picture;
use App\Models\Tournament;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use stdClass;

class AdminController extends Controller
{
    public function profile (Request $request) {
        return $request->get("user");
    }
    public function login (Request $request) {
        $admin = Admin::where("login", $request->login)->first();
        if (!$admin or !password_verify($request->password, $admin->password))
            abort (403, "Неверный логин или пароль");

        $cookie = utils::gen_cookie($admin, isadmin: true);
        $respcookie = Cookie::forever("admin", $cookie);

        return response()
            ->json(["Message" => "Успешная авторизация!", "cookie" => $cookie])
            ->withCookie($respcookie);
    }
    public function logout (Request $request) {
        $admin = $request->get("user");
        AdminCookie::where("cookie", $request->cookie("admin"))->delete();

        $respcookie = Cookie::forget("admin");

        return response()->json(["Message" => "Вы успешно вышли из системы администрации."])->withCookie($respcookie);
    }

    public function users (Request $request) {
        return utils::index(User::class, $request, true);
    }
    public function showUser (User $user, Request $request) {
        $user->levels = json_decode(env("LEVELS"), true);
        $user->courses = Course::all();
        foreach ($user->courses as &$course) {
            $course->lessons = Lesson::where("course_id", $course->id)->
            leftJoin('user_lessons', function($join) use ($user) {
                $join->on('lessons.id', '=', 'user_lessons.lesson_id')
                    ->where('user_lessons.user_id', '=', $user->id);
            })
                ->select('lessons.id', 'lessons.title', 'lessons.description', "lessons.count_tries", 'lessons.number', 'user_lessons.id as user_lesson_id', 'user_lessons.points as user_points', 'user_lessons.created_at as user_lesson_created_at')
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
        $user->crypto = json_decode($user->crypto, true);

        return $user;
    }

    public function courses () {
        $response = [];
        $response["courses"] =  Course::all();
        $response["levels"] = json_decode(env("LEVELS"), true);
        foreach ($response["courses"] as &$course) {
            $course->lessons = Lesson::where("course_id", $course->id)->get();
        }
        return response()->json($response);
    }

    public function deleteCourse (Course $course) {
        Lesson::where("course_id", $course->id)->delete();
        Tournament::where("object_id", $course->id)->delete();
        $course->delete();

        return $this->courses();
    }

    public function updateCourse (Course $course, AdminUpdateCourseRequest $request) {
        $validate = $request->validated();
        if (isset($validate["required_course"]) && $validate["required_course"] === $course->id) unset($validate["required_course"]);

        $course->update($validate);
        return $this->courses();
    }

    public function createCourse (AdminCreateCourseRequest $request) {
        $validate = $request->validated();
        Course::create($validate);

        return $this->courses();
    }

    public function deleteLesson (Lesson $lesson) {
        $lesson->delete();

        Lesson::where('course_id', $lesson->course_id)
            ->where('number', '>', $lesson->number)
            ->decrement('number');

        return $this->courses();
    }

    public function updateLesson (Lesson $lesson, AdminUpdateCourseRequest $request) {
        $validate = $request->validated();
        $courseId = $lesson->course_id;

        if (isset($validated['number']) && $validated['number'] != $lesson->number) {
            $oldNumber = $lesson->number;
            $newNumber = $validated['number'];

            if ($newNumber < $oldNumber) {
                Lesson::where('course_id', $courseId)
                    ->whereBetween('number', [$newNumber, $oldNumber - 1])
                    ->increment('number');
            } else {
                Lesson::where('course_id', $courseId)
                    ->whereBetween('number', [$oldNumber + 1, $newNumber])
                    ->decrement('number');
            }

            $validate->number = $newNumber;
        }

        $lesson->update($validate);
        return $this->courses();
    }

    public function createLesson (AdminCreateLessonRequest $request) {
        $lesson = $request->validated();
        $courseId = $lesson["course_id"];

        $newNumber = intval($lesson["number"]);
        $oldNumber = 9999;

        if ($newNumber < $oldNumber) {
            Lesson::where('course_id', $courseId)
                ->whereBetween('number', [$newNumber, $oldNumber - 1])
                ->increment('number');
        } else {
            Lesson::where('course_id', $courseId)
                ->whereBetween('number', [$oldNumber + 1, $newNumber])
                ->decrement('number');
        }

        $lesson["questions"] = json_encode($lesson["questions"], true);
        $lesson["videos"] = json_encode($lesson["videos"], true);

        Lesson::create($lesson);
        return $this->courses();
    }

    public function news (Request $request) {
        $categories = NewsCategory::all();
        $news = News::all();

        return response()->json(["news" => $news, "categories" => $categories]);
    }

    public function createNews (AdminCreateNewsRequest $request) {
        $validate = $request->validated();

        $picture = $request->file("image");
        $time = time();
        $url = "news/image_$time" . "." . $picture->extension();
        Storage::disk("public")->putFileAs("news", $picture, "image_$time" . "." . $picture->extension());
        $validate["image"] = $url;

        News::create($validate);

        return $this->news($request);
    }

    public function updateNews (News $news, AdminUpdateNewsRequest $request) {
        $validate = $request->validated();

        if ($request->has("image")) {
            Storage::disk("public")->delete($news->image);

            $picture = $request->file("image");
            $time = time();
            $url = "news/image_$time" . "." . $picture->extension();
            Storage::disk("public")->putFileAs("news", $picture, "image_$time" . "." . $picture->extension());
            $validate["image"] = $url;
        }

        $news->update($validate);
        return $this->news($request);
    }

    public function deleteNews (News $news, Request $request) {
        Storage::disk("public")->delete($news->image);
        $news->delete();

        return $this->news($request);
    }

    public function createNewsCategory (Request $request) {
        if (!$request->has("name")) abort(400, "Не указано название категории");
        NewsCategory::create(["name" => $request->name]);

        return $this->news($request);
    }

    public function deleteNewsCategory (NewsCategory $category, Request $request) {
        News::where("category_id", $category->id)->delete();
        $category->delete();

        return $this->news($request);
    }

    public function updateNewsCategory (NewsCategory $category, adminUpdateNewsCategoryRequest $request) {
        $validate = $request->validated();
        $category->update($validate);

        return $this->news($request);
    }

    public function tournaments () {
        $tournaments = Tournament::all();
        $lessons = Course::all();

        return response()->json(["tournaments" => $tournaments, "lessons" => $lessons]);
    }

    public function deleteTournament (Tournament $tournament, Request $request) {
        $tournament->delete();
        return $this->tournaments();
    }

    public function updateTournament (Tournament $tournament, AdminUpdateTournamentRequest $request) {
        $validate = $request->validated();
        if ($request->has("type")) {
            if ($validate["type"] === "lesson" && !isset($validate["object_id"]) && $tournament->object_id === 0)
                abort(400, "Не указан урок");
            if ($validate["type"] === 'time') $validate["object_id"] = 0;
        }

        $tournament->update($validate);
        return $this->tournaments();
    }

    public function createTournament (AdminCreateTournamentRequest $request) {
        $validate = $request->validated();
        if ($validate["type"] === "lesson" && !isset($validate["object_id"])) abort(400, "Не указан урок");

        Tournament::create($validate);
        return $this->tournaments();
    }

    public function currencies (Request $request) {
        return Currency::all();
    }

    public function updateCurrency (Currency $currency, AdminUpdateCurrencyRequest $request) {
        $currency->update($request->validated());
        return $this->currencies($request);
    }

    public function deleteCurrency (Currency $currency, Request $request) {
        $currency->delete();
        return $this->currencies($request);
    }

    public function createCurrency (AdminCreateCurrencyRequest $request) {
        Currency::create($request->validated());
        return $this->currencies($request);
    }

    public function achievements () {
        return Achievements::all();
    }

    public function updateAchievement (Achievements $achievement, AdminUpdateAchievementRequest $request) {
        $validate = $request->validated();
        if ($request->has("image")) {
            Storage::disk("public")->delete($achievement->image);

            $picture = $request->file("image");
            $time = time();
            $url = "achievements/image_$time" . "." . $picture->extension();
            Storage::disk("public")->putFileAs("achievements", $picture, "image_$time" . "." . $picture->extension());
            $validate["image"] = $url;
        }
        $achievement->update($validate);
        return $this->achievements();
    }

    public function deleteAchievement (Achievements $achievement, Request $request) {
        Storage::disk("public")->delete($achievement->image);
        $achievement->delete();
        return $this->achievements();
    }

    public function createAchievement (AdminCreateAchievementRequest $request) {
        $validate = $request->validated();

        $picture = $request->file("image");
        $time = time();
        $url = "achievements/image_$time" . "." . $picture->extension();
        Storage::disk("public")->putFileAs("achievements", $picture, "image_$time" . "." . $picture->extension());
        $validate["image"] = $url;

        Achievements::create($validate);
        return $this->achievements();
    }
}
