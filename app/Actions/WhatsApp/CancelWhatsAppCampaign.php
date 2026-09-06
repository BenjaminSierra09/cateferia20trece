<?php

namespace App\Actions\WhatsApp;

use App\Enums\WhatsAppCampaignRecipientStatus;
use App\Enums\WhatsAppCampaignStatus;
use App\Models\WhatsAppCampaign;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CancelWhatsAppCampaign
{
    public function execute(WhatsAppCampaign $campaign): void
    {
        DB::transaction(function () use ($campaign): void {
            $lockedCampaign = WhatsAppCampaign::query()->lockForUpdate()->findOrFail($campaign->id);

            if (! $lockedCampaign->status->canCancel()) {
                throw ValidationException::withMessages([
                    'campaign' => 'Esta campaña ya no se puede cancelar.',
                ]);
            }

            $lockedCampaign->update([
                'status' => WhatsAppCampaignStatus::Cancelled,
                'cancelled_at' => now(),
            ]);

            $lockedCampaign->recipients()
                ->where('status', WhatsAppCampaignRecipientStatus::Pending)
                ->update([
                    'status' => WhatsAppCampaignRecipientStatus::Skipped,
                    'error_message' => 'Campaña cancelada antes del envío.',
                ]);
        });
    }
}
