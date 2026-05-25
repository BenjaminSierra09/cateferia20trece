<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['branch_id', 'beverage_id', 'size_id', 'is_available'])]
class BranchBeverageSizeAvailability extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_available' => 'boolean',
        ];
    }

    /**
     * Get the branch for the availability rule.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the beverage for the availability rule.
     */
    public function beverage(): BelongsTo
    {
        return $this->belongsTo(Beverage::class);
    }

    /**
     * Get the size for the availability rule.
     */
    public function size(): BelongsTo
    {
        return $this->belongsTo(Size::class);
    }
}
