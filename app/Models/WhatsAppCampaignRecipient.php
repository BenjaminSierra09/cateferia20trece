<?php

namespace App\Models;

use App\Enums\WhatsAppCampaignRecipientStatus;
use Database\Factories\WhatsAppCampaignRecipientFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['whatsapp_campaign_id', 'customer_id', 'whatsapp_message_id', 'phone', 'name', 'message_preview', 'parameters', 'consented_at', 'consent_source', 'status', 'provider_message_id', 'error_code', 'error_message', 'attempted_at', 'sent_at', 'delivered_at', 'read_at'])]
class WhatsAppCampaignRecipient extends Model
{
    /** @use HasFactory<WhatsAppCampaignRecipientFactory> */
    use HasFactory;

    protected $table = 'whatsapp_campaign_recipients';

    protected $attributes = [
        'status' => WhatsAppCampaignRecipientStatus::Pending->value,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'parameters' => 'array',
            'consented_at' => 'datetime',
            'status' => WhatsAppCampaignRecipientStatus::class,
            'attempted_at' => 'datetime',
            'sent_at' => 'datetime',
            'delivered_at' => 'datetime',
            'read_at' => 'datetime',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(WhatsAppCampaign::class, 'whatsapp_campaign_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(WhatsAppMessage::class, 'whatsapp_message_id');
    }
}
