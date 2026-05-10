<?php

namespace App\Models;

use App\Observers\CustomerQrCodeObserver;
use Database\Factories\CustomerQrCodeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy([CustomerQrCodeObserver::class])]
#[Fillable(['customer_id', 'uuid', 'is_active', 'last_scanned_at'])]
class CustomerQrCode extends Model
{
    /** @use HasFactory<CustomerQrCodeFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_scanned_at' => 'datetime',
        ];
    }

    /**
     * Get the customer assigned to the QR code.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
