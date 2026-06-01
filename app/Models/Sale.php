<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\SaleStatus;
use Database\Factories\SaleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['billing_token', 'branch_id', 'user_id', 'customer_id', 'work_session_id', 'sold_at', 'payment_method', 'payment_breakdown', 'status', 'subtotal', 'discount_total', 'reward_redeemed_total', 'total', 'discount_concept', 'notes'])]
class Sale extends Model
{
    /** @use HasFactory<SaleFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function (Sale $sale): void {
            if (blank($sale->billing_token)) {
                $sale->billing_token = self::newBillingToken();
            }
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sold_at' => 'datetime',
            'subtotal' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'reward_redeemed_total' => 'decimal:2',
            'total' => 'decimal:2',
            'payment_method' => PaymentMethod::class,
            'payment_breakdown' => 'array',
            'status' => SaleStatus::class,
        ];
    }

    /**
     * Get the branch for the sale.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the collaborator for the sale.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the customer for the sale.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the work session for the sale.
     */
    public function workSession(): BelongsTo
    {
        return $this->belongsTo(WorkSession::class);
    }

    /**
     * Get the sale items.
     */
    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    /**
     * Get the debt movements automatically created from the sale.
     */
    public function debtMovements(): HasMany
    {
        return $this->hasMany(CustomerDebtMovement::class);
    }

    /**
     * Get the Mercado Pago Point order linked to the sale.
     */
    public function mercadoPagoPointOrder(): HasOne
    {
        return $this->hasOne(MercadoPagoPointOrder::class);
    }

    /**
     * Determine whether the sale can still be cancelled.
     */
    public function canBeCancelled(): bool
    {
        return $this->status === SaleStatus::Completed;
    }

    public function ensureBillingToken(): string
    {
        if (filled($this->billing_token)) {
            return $this->billing_token;
        }

        $this->forceFill([
            'billing_token' => self::newBillingToken(),
        ])->saveQuietly();

        return $this->billing_token;
    }

    public function paymentMethodSummary(): string
    {
        if ($this->payment_method !== PaymentMethod::Mixed || blank($this->payment_breakdown)) {
            return $this->payment_method->label();
        }

        $breakdown = collect($this->payment_breakdown)
            ->map(fn ($amount) => round((float) $amount, 2))
            ->filter(fn (float $amount): bool => $amount > 0)
            ->map(function (float $amount, string $method): string {
                $label = PaymentMethod::tryFrom($method)?->label() ?? $method;

                return sprintf('%s $%s', $label, number_format($amount, 2));
            })
            ->values()
            ->implode(', ');

        return $breakdown !== ''
            ? $this->payment_method->label().' ('.$breakdown.')'
            : $this->payment_method->label();
    }

    public function suggestedInvoicePaymentForm(): ?string
    {
        return match ($this->payment_method) {
            PaymentMethod::Cash => '01',
            PaymentMethod::Transfer => '03',
            default => null,
        };
    }

    public static function newBillingToken(): string
    {
        do {
            $token = self::randomBillingToken();
        } while (self::query()->where('billing_token', $token)->exists());

        return $token;
    }

    private static function randomBillingToken(): string
    {
        $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $token = '';

        for ($i = 0; $i < 7; $i++) {
            $token .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }

        return $token;
    }
}
