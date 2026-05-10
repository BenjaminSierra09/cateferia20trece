<?php

use App\Enums\RewardTier;
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
        Schema::create('customers', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->date('birthday')->nullable();
            $table->string('email')->nullable();
            $table->decimal('reward_balance', 10, 2)->default(0);
            $table->unsignedInteger('reward_year')->default((int) now()->format('Y'));
            $table->unsignedInteger('annual_drink_count')->default(0);
            $table->string('reward_tier')->default(RewardTier::Bronze->value);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
