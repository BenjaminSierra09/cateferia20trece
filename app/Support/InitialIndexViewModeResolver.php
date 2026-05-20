<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

class InitialIndexViewModeResolver
{
    public function resolve(Request $request): string
    {
        if ($request->query->has('view')) {
            return in_array($request->query('view'), ['list', 'grid'], true)
                ? (string) $request->query('view')
                : 'list';
        }

        $userAgent = Str::lower($request->userAgent() ?? '');

        return Str::contains($userAgent, [
            'android',
            'iphone',
            'ipad',
            'ipod',
            'mobile',
            'opera mini',
            'windows phone',
        ]) ? 'grid' : 'list';
    }
}
