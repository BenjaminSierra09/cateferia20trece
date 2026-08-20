<?php

namespace App\Models;

use App\Enums\WhatsAppMessageDirection;
use App\Enums\WhatsAppMessageStatus;
use Database\Factories\WhatsAppMessageReactionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['whatsapp_message_id', 'sent_by_user_id', 'provider_message_id', 'direction', 'emoji', 'status', 'error_code', 'reacted_at'])]
class WhatsAppMessageReaction extends Model
{
    /** @use HasFactory<WhatsAppMessageReactionFactory> */
    use HasFactory;

    protected $table = 'whatsapp_message_reactions';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'direction' => WhatsAppMessageDirection::class,
            'status' => WhatsAppMessageStatus::class,
            'reacted_at' => 'datetime',
        ];
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(WhatsAppMessage::class, 'whatsapp_message_id');
    }

    public function sentBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by_user_id');
    }
}
