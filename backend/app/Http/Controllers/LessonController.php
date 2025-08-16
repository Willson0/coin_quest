<?php

namespace App\Http\Controllers;

use App\Http\utils;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\User;
use App\Models\UserLesson;
use Illuminate\Http\Request;

class LessonController extends Controller
{
    public function show (Lesson $lesson, Request $request) {
        $user = User::where("telegram_id", $request["initData"]["user"]["id"])->firstOrFail();
        utils::checkAccessToLesson($user, $lesson);

        $lesson->oldResult = $lesson->userLessons()->where('user_id', $user->id)->latest()->first()?->points;
        $lesson->videos = json_decode($lesson->videos, true);

        $questions = json_decode($lesson->questions, true);
        foreach ($questions as &$question) {
            unset($question['right_answer']);
        }
        $lesson->questions = $questions;

        return response()->json($lesson);
    }

    public function checkAnswers (Lesson $lesson, Request $request)
    {
        $user = User::where("telegram_id", $request["initData"]["user"]["id"])->firstOrFail();
        if (!isset($request["answers"])) return;
        utils::checkAccessToLesson($user, $lesson);

        $rightAnswers = json_decode($lesson->questions, true);
        if (count($rightAnswers) != count($request["answers"])) abort(403);

        $count = 0;
        for ($i = 0; $i < count($rightAnswers); $i++) {
            if ($rightAnswers[$i]["right_answer"] == $request["answers"][$i]) $count++;
        }
        $points = round($count / count($rightAnswers) * 100);

        $lastPassing = $lesson->userLessons()->where('user_id', $user->id)->latest()->first();
        if ($lesson->count_tries > 0) {
            $user_counts = $lesson->userLessons()->where('user_id', $user->id)->count();
            if ($lastPassing && $lastPassing->points >= 50) $lastPassing->update(["points" => max($points, $lastPassing->points)]);
            else if ($user_counts + 1 >= $lesson->count_tries && $points < 50) {
                $courseLessons = Lesson::where('course_id', $lesson->course_id)->get()->pluck('id');
                UserLesson::where('user_id', $user->id)->whereIn('lesson_id', $courseLessons)->delete();
            } else UserLesson::create([
                    "user_id" => $user->id,
                    "lesson_id" => $lesson->id,
                    "points" => $points,
                ]);
        } else {
            if ($lastPassing) $lastPassing->update(["points" => max($points, $lastPassing->points)]);
            else UserLesson::create([
                "user_id" => $user->id,
                "lesson_id" => $lesson->id,
                "points" => $points,
            ]);
        }

        return response()->json(["points" => $points]);
    }
}
