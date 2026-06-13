<?php

use App\Enums\CashMovementType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_movements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('work_session_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type')->default(CashMovementType::Expense->value);
            $table->decimal('amount', 10, 2);
            $table->string('concept');
            $table->text('notes')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['branch_id', 'occurred_at']);
            $table->index(['work_session_id', 'occurred_at']);
        });

        Schema::create('cash_register_cuts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('work_session_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('period_start_at')->nullable();
            $table->timestamp('cut_at');
            $table->decimal('opening_cash_amount', 10, 2)->default(0);
            $table->decimal('counted_cash_amount', 10, 2);
            $table->decimal('expected_cash_amount', 10, 2);
            $table->decimal('difference_amount', 10, 2)->default(0);
            $table->decimal('cash_sales_total', 10, 2)->default(0);
            $table->decimal('manual_income_total', 10, 2)->default(0);
            $table->decimal('manual_expense_total', 10, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['branch_id', 'cut_at']);
            $table->index(['work_session_id', 'cut_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_register_cuts');
        Schema::dropIfExists('cash_movements');
    }
};
