<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller as BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

abstract class Controller extends BaseController
{
    protected function perPage(Request $request, int $default = 15, int $max = 100): int
    {
        return max(1, min($request->integer('per_page', $default), $max));
    }

    protected function slugFromName(string $name): string
    {
        return Str::slug($name);
    }
}
