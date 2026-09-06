<?php

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
        Schema::table('customers', function (Blueprint $table) {
            $table->timestamp('whatsapp_marketing_opted_in_at')->nullable()->after('notes');
            $table->timestamp('whatsapp_marketing_opted_out_at')->nullable()->after('whatsapp_marketing_opted_in_at');
            $table->string('whatsapp_marketing_consent_source', 64)->nullable()->after('whatsapp_marketing_opted_out_at');
            $table->string('whatsapp_marketing_consent_version', 32)->nullable()->after('whatsapp_marketing_consent_source');
            $table->string('whatsapp_marketing_consented_phone', 32)->nullable()->after('whatsapp_marketing_consent_version');

            $table->index(
                ['is_active', 'whatsapp_marketing_opted_in_at', 'whatsapp_marketing_opted_out_at'],
                'customers_whatsapp_marketing_eligible_index',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex('customers_whatsapp_marketing_eligible_index');
            $table->dropColumn([
                'whatsapp_marketing_opted_in_at',
                'whatsapp_marketing_opted_out_at',
                'whatsapp_marketing_consent_source',
                'whatsapp_marketing_consent_version',
                'whatsapp_marketing_consented_phone',
            ]);
        });
    }
};
