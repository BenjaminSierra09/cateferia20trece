<?php

namespace App\Models;

use Database\Factories\WhatsAppConversationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['phone', 'profile_name', 'customer_id', 'conversation_id', 'last_message_id', 'last_inbound_at', 'last_outbound_at', 'last_message_at'])]
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
            'last_message_at' => 'datetime',
        ];
    }

    /**
     * Get the customer linked to this WhatsApp conversation, if registered.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(WhatsAppMessage::class, 'whatsapp_conversation_id');
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(WhatsAppMessage::class, 'whatsapp_conversation_id')->latestOfMany('sent_at');
    }

    public function hasOpenCustomerServiceWindow(): bool
    {
        return $this->last_inbound_at?->greaterThanOrEqualTo(now()->subDay()) ?? false;
    }

    public function displayName(): string
    {
        return $this->customer?->name ?? $this->profile_name ?? '+'.$this->phone;
    }
}
