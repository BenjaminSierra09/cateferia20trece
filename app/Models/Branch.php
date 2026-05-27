<?php

namespace App\Models;

use Database\Factories\BranchFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Branch extends Model
{
    /** @use HasFactory<BranchFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'address',
        'city',
        'phone',
        'operating_hours',
        'is_active',
        'mercado_pago_is_active',
        'mercado_pago_access_token',
        'mercado_pago_public_key',
        'mercado_pago_default_terminal_id',
        'mercado_pago_default_terminal_name',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'mercado_pago_is_active' => 'boolean',
            'mercado_pago_access_token' => 'encrypted',
            'mercado_pago_public_key' => 'encrypted',
        ];
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

    /**
     * Get the customization price overrides for the branch.
     */
    public function customizationSizePriceOverrides(): HasMany
    {
        return $this->hasMany(BranchCustomizationSizePriceOverride::class);
    }

    /**
     * Get Mercado Pago Point orders issued from the branch.
     */
    public function mercadoPagoPointOrders(): HasMany
    {
        return $this->hasMany(MercadoPagoPointOrder::class);
    }
}
