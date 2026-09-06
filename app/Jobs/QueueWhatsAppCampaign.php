<?php

namespace App\Jobs;

use App\Enums\WhatsAppCampaignRecipientStatus;
use App\Enums\WhatsAppCampaignStatus;
use App\Models\WhatsAppCampaign;
use App\Models\WhatsAppCampaignRecipient;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\Backoff;
use Illuminate\Queue\Attributes\Timeout;
use Illuminate\Queue\Attributes\Tries;
use Illuminate\Support\Facades\Log;
use Throwable;

#[Tries(3)]
#[Backoff([10, 30, 60])]
#[Timeout(60)]
class QueueWhatsAppCampaign implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $uniqueFor = 3600;

    public function __construct(public int $campaignId) {}

    public function uniqueId(): string
    {
        return (string) $this->campaignId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $campaign = WhatsAppCampaign::query()->find($this->campaignId);

        if ($campaign === null || in_array($campaign->status, [
            WhatsAppCampaignStatus::Completed,
            WhatsAppCampaignStatus::Cancelled,
        ], true)) {
            return;
        }

        $campaign->update([
            'status' => WhatsAppCampaignStatus::Sending,
            'started_at' => $campaign->started_at ?? now(),
            'completed_at' => null,
        ]);

        $dispatched = false;

        WhatsAppCampaignRecipient::query()
            ->where('whatsapp_campaign_id', $campaign->id)
            ->where('status', WhatsAppCampaignRecipientStatus::Pending)
            ->select('id')
            ->orderBy('id')
            ->chunkById(250, function ($recipients) use (&$dispatched): void {
                foreach ($recipients as $recipient) {
                    $dispatched = true;
                    SendWhatsAppCampaignRecipient::dispatch($recipient->id);
                }
            });

        if (! $dispatched) {
            $campaign->refresh()->markCompletedIfFinished();
        }
    }

    public function failed(?Throwable $exception): void
    {
        WhatsAppCampaign::query()
            ->whereKey($this->campaignId)
            ->whereNotIn('status', [
                WhatsAppCampaignStatus::Completed,
                WhatsAppCampaignStatus::Cancelled,
            ])
            ->update([
                'status' => WhatsAppCampaignStatus::Failed,
                'completed_at' => now(),
            ]);

        Log::error('No fue posible preparar una campaña de WhatsApp.', [
            'campaign_id' => $this->campaignId,
            'exception' => $exception,
        ]);
    }
}
