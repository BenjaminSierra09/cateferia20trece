<?php

namespace App\Models;

use App\Enums\WorkSessionStatus;
use Database\Factories\WorkSessionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['user_id', 'branch_id', 'work_date', 'clock_in_at', 'clock_out_at', 'status', 'notes'])]
class WorkSession extends Model
{
    /** @use HasFactory<WorkSessionFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'work_date' => 'date',
            'clock_in_at' => 'datetime',
            'clock_out_at' => 'datetime',
            'status' => WorkSessionStatus::class,
        ];
    }

    /**
     * Get the collaborator for the work session.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the branch for the work session.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the sales captured during the work session.
     */
    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function cashMovements(): HasMany
    {
        return $this->hasMany(CashMovement::class);
    }

    public function cashRegisterCuts(): HasMany
    {
        return $this->hasMany(CashRegisterCut::class);
    }
}
