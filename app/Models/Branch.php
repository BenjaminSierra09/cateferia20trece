<?php

namespace App\Models;

use Database\Factories\BranchFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'address', 'city', 'phone', 'operating_hours', 'is_active'])]
class Branch extends Model
{
    /** @use HasFactory<BranchFactory> */
    use HasFactory;

    /**
     * Get the collaborators assigned to the branch.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get the work sessions for the branch.
     */
    public function workSessions(): HasMany
    {
        return $this->hasMany(WorkSession::class);
    }

    /**
     * Get the sales for the branch.
     */
    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }
}
