<?php

namespace App\Http\Controllers;

use Domain\Joke\JokeRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class JokeController extends Controller
{
    public function index(Request $request, JokeRepository $jokes): JsonResponse
    {
        $limit = min(max((int) $request->integer('limit', 50), 1), 200);

        return response()->json($jokes->latest($limit));
    }
}
