<?php

namespace App\Models;

use Database\Factories\WhatsAppConversationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['phone', 'customer_id', 'conversation_id', 'last_message_id', 'last_inbound_at', 'last_outbound_at'])]
class WhatsAppConversation extends Model
{
    /** @use HasFactory<WhatsAppConversationFactory> */
    use HasFactory;

    protected $table = 'whatsapp_conversations';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_inbound_at' => 'datetime',
            'last_outbound_at' => 'datetime',
        ];
    }

    /**
     * Get the customer linked to this WhatsApp conversation, if registered.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
