<?php

namespace App\Http\Middleware;

use App\Services\WorkSessionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureWorkSessionIsConfirmed
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $workSessionService = app(WorkSessionService::class);

        if ($request->user() !== null
            && ! $request->routeIs('dashboard.work-session.*')
            && ! $request->is('api/*')
            && $workSessionService->currentFor($request->user()) === null) {
            return redirect()->route('dashboard.work-session.check-in');
        }

        return $next($request);
    }
}
