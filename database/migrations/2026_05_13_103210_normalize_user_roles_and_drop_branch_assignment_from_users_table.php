<?php

use App\Enums\UserRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('users')
            ->where(function ($query): void {
                $query->whereNull('role')
                    ->orWhere('role', '!=', UserRole::Admin->value);
            })
            ->update(['role' => UserRole::Employee->value]);

        if (Schema::hasColumn('users', 'branch_id')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('branch_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('users', 'branch_id')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->foreignId('branch_id')->nullable()->after('role')->constrained()->nullOnDelete();
            });
        }
    }
};
