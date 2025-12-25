<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TvOption;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TvOptionController extends Controller
{
    private const CATEGORIES = [
        'SOUND',
        'WALL',
        'STORAGE',
        'EXTENDED_WARRANTY',
    ];

    public function index(Request $request): JsonResponse
    {
        $query = TvOption::query();

        if ($request->filled('category')) {
            $request->validate([
                'category' => ['string', Rule::in(self::CATEGORIES)],
            ]);
            $query->where('category', $request->string('category')->toString());
        }

        return response()->json($query->orderBy('id')->get());
    }

    public function show(TvOption $tvOption): JsonResponse
    {
        return response()->json($tvOption);
    }
}
