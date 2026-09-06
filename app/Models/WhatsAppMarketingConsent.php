<?php

namespace App\Models;

use Database\Factories\WhatsAppMarketingConsentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['customer_id', 'recorded_by_user_id', 'phone', 'status', 'source', 'consent_text_version', 'ip_address', 'occurred_at'])]
class WhatsAppMarketingConsent extends Model
{
    /** @use HasFactory<WhatsAppMarketingConsentFactory> */
    use HasFactory;

    protected $table = 'whatsapp_marketing_consents';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }
}
