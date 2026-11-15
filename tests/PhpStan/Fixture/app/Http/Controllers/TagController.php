<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

class TagController
{
    public function index(): JsonResponse
    {
        return response()->json([]);
    }
}
