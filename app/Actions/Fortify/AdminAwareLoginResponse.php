<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Contracts\LoginResponse;

class AdminAwareLoginResponse implements LoginResponse
{
    public function toResponse($request): RedirectResponse
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user?->canAccessDashboard()) {
            return redirect()->intended(route('dashboard'));
        }

        Auth::guard(config('fortify.guard'))->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('home')
            ->with('status', 'Tu usuario solo puede operar desde la app de Android.');
    }
}
