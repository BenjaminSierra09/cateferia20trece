<?php

use App\Enums\TableOrderStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dining_tables', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedSmallInteger('seats')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['branch_id', 'name']);
        });

        Schema::create('table_orders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('work_session_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('merged_into_id')->nullable()->constrained('table_orders')->nullOnDelete();
            $table->string('status')->default(TableOrderStatus::Open->value);
            $table->string('label')->nullable();
            $table->unsignedSmallInteger('guest_count')->default(1);
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('dining_table_table_order', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('dining_table_id')->constrained()->cascadeOnDelete();
            $table->foreignId('table_order_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['dining_table_id', 'table_order_id'], 'dining_table_order_unique');
        });

        Schema::create('table_order_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('table_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('beverage_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('size_id')->nullable()->constrained()->nullOnDelete();
            $table->string('item_name');
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('base_price', 10, 2);
            $table->decimal('unit_price', 10, 2);
            $table->decimal('line_total', 10, 2);
            $table->json('customization_option_ids')->nullable();
            $table->string('guest_name')->nullable();
            $table->text('special_instructions')->nullable();
            $table->timestamps();
        });

        Schema::create('table_order_item_customizations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('table_order_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customization_option_id')->nullable()->constrained()->nullOnDelete();
            $table->string('customization_type_name')->nullable();
            $table->string('customization_name');
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('price', 10, 2)->default(0);
            $table->timestamps();
        });

        Schema::table('sales', function (Blueprint $table): void {
            $table->foreignId('table_order_id')->nullable()->after('work_session_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('table_order_id');
        });

        Schema::dropIfExists('table_order_item_customizations');
        Schema::dropIfExists('table_order_items');
        Schema::dropIfExists('dining_table_table_order');
        Schema::dropIfExists('table_orders');
        Schema::dropIfExists('dining_tables');
    }
};
