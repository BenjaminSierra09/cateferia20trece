<?php

namespace App\Models;

use App\Enums\WhatsAppMessageDirection;
use App\Enums\WhatsAppMessageStatus;
use Database\Factories\WhatsAppMessageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['whatsapp_conversation_id', 'sent_by_user_id', 'provider_message_id', 'direction', 'type', 'body', 'media_path', 'media_mime_type', 'status', 'error_code', 'sent_at'])]
class WhatsAppMessage extends Model
{
    /** @use HasFactory<WhatsAppMessageFactory> */
    use HasFactory;

    protected $table = 'whatsapp_messages';

    protected $attributes = [
        'type' => 'text',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'direction' => WhatsAppMessageDirection::class,
            'status' => WhatsAppMessageStatus::class,
            'sent_at' => 'datetime',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(WhatsAppConversation::class, 'whatsapp_conversation_id');
    }

    public function sentBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by_user_id');
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(WhatsAppMessageReaction::class, 'whatsapp_message_id');
    }

    public function previewText(): string
    {
        return match ($this->type) {
            'image' => filled($this->body) ? '📷 '.$this->body : '📷 Foto',
            default => $this->body ?? 'Mensaje sin contenido',
        };
    }

    public function canReceiveReaction(): bool
    {
        return filled($this->provider_message_id)
            && $this->sent_at !== null
            && $this->sent_at->greaterThanOrEqualTo(now()->subDays(30));
    }
}
