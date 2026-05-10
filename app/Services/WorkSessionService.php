<?php

namespace App\Services;

use App\Enums\WorkSessionStatus;
use App\Models\Branch;
use App\Models\User;
use App\Models\WorkSession;

class WorkSessionService
{
    /**
     * Get the current work session for the user.
     */
    public function currentFor(User $user): ?WorkSession
    {
        return WorkSession::query()
            ->whereBelongsTo($user)
            ->whereDate('work_date', today())
            ->first();
    }

    /**
     * Start or update today's work session.
     */
    public function start(User $user, Branch $branch, ?string $notes = null): WorkSession
    {
        return WorkSession::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'work_date' => today(),
            ],
            [
                'branch_id' => $branch->id,
                'clock_in_at' => now(),
                'clock_out_at' => null,
                'status' => WorkSessionStatus::Open,
                'notes' => $notes,
            ],
        );
    }

    /**
     * Close the provided work session.
     */
    public function close(WorkSession $workSession): WorkSession
    {
        $workSession->forceFill([
            'clock_out_at' => now(),
            'status' => WorkSessionStatus::Closed,
        ])->save();

        return $workSession;
    }
}
