<?php

use App\Enums\PaymentMethod;
use App\Enums\SaleStatus;
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
        Schema::create('sales', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('work_session_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('sold_at');
            $table->string('payment_method')->default(PaymentMethod::Cash->value);
            $table->string('status')->default(SaleStatus::Completed->value);
            $table->decimal('subtotal', 10, 2);
            $table->decimal('discount_total', 10, 2)->default(0);
            $table->decimal('reward_redeemed_total', 10, 2)->default(0);
            $table->decimal('total', 10, 2);
            $table->string('discount_concept')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
