<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class StatusController extends Controller
{
    public function status(): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'app' => config('app.name'),
            'time' => now()->toIso8601String(),
        ]);
    }
}
