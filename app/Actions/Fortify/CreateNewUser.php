<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        $normalizedInput = [
            ...$input,
            'name' => trim($input['name']),
            'username' => str($input['username'])->trim()->lower()->toString(),
            'email' => str($input['email'])->trim()->lower()->toString(),
        ];

        Validator::make($normalizedInput, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
        ])->validate();

        return User::create([
            'name' => $normalizedInput['name'],
            'username' => $normalizedInput['username'],
            'email' => $normalizedInput['email'],
            'password' => $normalizedInput['password'],
            'role' => UserRole::Cashier,
            'is_active' => true,
        ]);
    }
}
