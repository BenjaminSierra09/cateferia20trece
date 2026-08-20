<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('whatsapp_messages')
            ->where('direction', 'inbound')
            ->update(['sent_at' => DB::raw('created_at')]);

        DB::table('whatsapp_conversations')
            ->select('id')
            ->orderBy('id')
            ->chunkById(200, function ($conversations): void {
                foreach ($conversations as $conversation) {
                    $lastInboundAt = DB::table('whatsapp_messages')
                        ->where('whatsapp_conversation_id', $conversation->id)
                        ->where('direction', 'inbound')
                        ->max('created_at');
                    $lastMessageAt = DB::table('whatsapp_messages')
                        ->where('whatsapp_conversation_id', $conversation->id)
                        ->max('sent_at');

                    DB::table('whatsapp_conversations')
                        ->where('id', $conversation->id)
                        ->update([
                            'last_inbound_at' => $lastInboundAt,
                            'last_message_at' => $lastMessageAt,
                        ]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        /**
         * Historical timestamps cannot be restored reliably after normalization.
         */
    }
};
