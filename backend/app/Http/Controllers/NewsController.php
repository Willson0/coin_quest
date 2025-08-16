<?php

namespace App\Http\Controllers;

use App\Http\utils;
use App\Models\News;
use App\Models\NewsCategory;
use App\Models\User;
use Illuminate\Http\Request;

class NewsController extends Controller
{
    public function index(Request $request) {
        $user = User::where("telegram_id", $request["initData"]["user"]["id"])->firstOrFail();
        $offset = $request["offset"] ?? 0;

        if ($request["category"] === -1) return response()->json(News::limit(10)->offset($offset)->orderBy('id', 'desc')->get());
        else return response()->json(News::where("category_id", $request["category"])->limit(10)->offset($offset)->orderBy('id', 'desc')->get());
    }
}
