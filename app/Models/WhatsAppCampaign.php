<?php

namespace App\Models;

use App\Enums\WhatsAppCampaignRecipientStatus;
use App\Enums\WhatsAppCampaignStatus;
use Database\Factories\WhatsAppCampaignFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['created_by_user_id', 'name', 'template_name', 'template_language', 'message_preview', 'template_parameters', 'status', 'recipient_count', 'started_at', 'completed_at', 'cancelled_at'])]
class WhatsAppCampaign extends Model
{
    /** @use HasFactory<WhatsAppCampaignFactory> */
    use HasFactory;

    protected $table = 'whatsapp_campaigns';

    protected $attributes = [
        'template_language' => 'es_MX',
        'status' => WhatsAppCampaignStatus::Queued->value,
        'recipient_count' => 0,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'template_parameters' => 'array',
            'status' => WhatsAppCampaignStatus::class,
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(WhatsAppCampaignRecipient::class, 'whatsapp_campaign_id');
    }

    public function markCompletedIfFinished(): void
    {
        if ($this->status !== WhatsAppCampaignStatus::Sending
            || $this->recipients()
                ->whereIn('status', [
                    WhatsAppCampaignRecipientStatus::Pending,
                    WhatsAppCampaignRecipientStatus::Sending,
                ])
                ->exists()) {
            return;
        }

        $this->update([
            'status' => WhatsAppCampaignStatus::Completed,
            'completed_at' => now(),
        ]);
    }
}
