<?php

namespace App\Actions\WhatsApp;

use App\Enums\WhatsAppCampaignRecipientStatus;
use App\Enums\WhatsAppCampaignStatus;
use App\Jobs\QueueWhatsAppCampaign;
use App\Models\WhatsAppCampaign;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RetryWhatsAppCampaignFailures
{
    public function execute(WhatsAppCampaign $campaign): int
    {
        $count = DB::transaction(function () use ($campaign): int {
            $lockedCampaign = WhatsAppCampaign::query()->lockForUpdate()->findOrFail($campaign->id);

            if ($lockedCampaign->status === WhatsAppCampaignStatus::Cancelled) {
                throw ValidationException::withMessages([
                    'campaign' => 'Una campaña cancelada no se puede reintentar.',
                ]);
            }

            if (! in_array($lockedCampaign->status, [
                WhatsAppCampaignStatus::Completed,
                WhatsAppCampaignStatus::Failed,
            ], true)) {
                throw ValidationException::withMessages([
                    'campaign' => 'Espera a que termine la campaña antes de reintentar sus fallos.',
                ]);
            }

            if ($lockedCampaign->recipients()
                ->where('status', WhatsAppCampaignRecipientStatus::Sending)
                ->exists()) {
                throw ValidationException::withMessages([
                    'campaign' => 'Todavía hay un envío en proceso. Espera antes de reintentar.',
                ]);
            }

            $retryableStatuses = [WhatsAppCampaignRecipientStatus::Failed];

            if ($lockedCampaign->status === WhatsAppCampaignStatus::Failed) {
                $retryableStatuses[] = WhatsAppCampaignRecipientStatus::Pending;
            }

            $retryableRecipients = $lockedCampaign->recipients()
                ->whereIn('status', $retryableStatuses);
            $count = $retryableRecipients->count();

            if ($count === 0) {
                throw ValidationException::withMessages([
                    'campaign' => 'No hay envíos pendientes o fallidos que se puedan reintentar con seguridad.',
                ]);
            }

            $retryableRecipients
                ->update([
                    'status' => WhatsAppCampaignRecipientStatus::Pending,
                    'error_code' => null,
                    'error_message' => null,
                    'attempted_at' => null,
                ]);

            $lockedCampaign->update([
                'status' => WhatsAppCampaignStatus::Queued,
                'completed_at' => null,
            ]);

            return $count;
        });

        QueueWhatsAppCampaign::dispatch($campaign->id);

        return $count;
    }
}
