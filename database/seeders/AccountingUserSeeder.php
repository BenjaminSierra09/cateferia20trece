<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AccountingUserSeeder extends Seeder
{
    private const EMAIL = 'contabilidad@cafeteria20trece.com';

    public function run(): void
    {
        $user = User::query()->where('email', self::EMAIL)->first();

        if ($user !== null) {
            $user->forceFill([
                'name' => 'Contabilidad Café 20Trece',
                'username' => 'contabilidad',
                'role' => UserRole::Accounting,
                'is_active' => true,
            ])->save();

            $this->command?->info('Usuario de contabilidad actualizado sin cambiar contraseña: '.self::EMAIL);

            return;
        }

        $password = Str::password(length: 24);

        User::query()->create([
            'name' => 'Contabilidad Café 20Trece',
            'username' => 'contabilidad',
            'email' => self::EMAIL,
            'password' => $password,
            'role' => UserRole::Accounting,
            'is_active' => true,
        ])->forceFill(['email_verified_at' => now()])->save();

        $this->command?->warn('Usuario de contabilidad creado: '.self::EMAIL);
        $this->command?->warn('Contraseña temporal: '.$password);
    }
}
