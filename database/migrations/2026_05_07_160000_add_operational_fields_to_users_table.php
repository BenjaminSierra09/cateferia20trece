<?php

use App\Enums\UserRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('username')->after('name')->unique();
            $table->string('role')->after('password')->default(UserRole::Employee->value);
            $table->foreignId('branch_id')->nullable()->after('role')->constrained()->nullOnDelete();
            $table->boolean('is_active')->after('branch_id')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('branch_id');
            $table->dropColumn(['username', 'role', 'is_active']);
        });
    }
};
