<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureDashboardAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user === null) {
            abort(403);
        }

        if (! $user->canAccessDashboard()) {
            auth()->logout();

            return redirect()
                ->route('home')
                ->with('status', 'Tu usuario solo puede operar desde la app de Android.');
        }

        return $next($request);
    }
}
