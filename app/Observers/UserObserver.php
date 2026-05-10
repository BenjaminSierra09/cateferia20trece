<?php

namespace App\Observers;

use App\Models\User;
use Illuminate\Support\Str;

class UserObserver
{
    /**
     * Handle the User "saving" event.
     */
    public function saving(User $user): void
    {
        $user->name = trim($user->name);
        $user->username = Str::lower(trim($user->username));
        $user->email = Str::lower(trim($user->email));
    }
}
