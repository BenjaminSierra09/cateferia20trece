<?php

namespace App\Models;

use App\Enums\RewardTier;
use App\Observers\CustomerObserver;
use App\Support\TonalpohualliCalendar;
use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[ObservedBy([CustomerObserver::class])]
#[Fillable(['name', 'phone', 'birthday', 'email', 'notes', 'reward_balance', 'reward_year', 'annual_drink_count', 'reward_tier', 'is_active'])]
class Customer extends Model
{
    /** @use HasFactory<CustomerFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'birthday' => 'date',
            'reward_balance' => 'decimal:2',
            'is_active' => 'boolean',
            'reward_tier' => RewardTier::class,
        ];
    }

    /**
     * Get the sales for the customer.
     */
    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    /**
     * Get the QR codes assigned to the customer.
     */
    public function qrCodes(): HasMany
    {
        return $this->hasMany(CustomerQrCode::class);
    }

    /**
     * Get reward transactions for the customer.
     */
    public function rewardTransactions(): HasMany
    {
        return $this->hasMany(RewardTransaction::class);
    }

    /**
     * Get debt movements for the customer.
     */
    public function debtMovements(): HasMany
    {
        return $this->hasMany(CustomerDebtMovement::class)
            ->orderByDesc('recorded_at')
            ->orderByDesc('id');
    }

    /**
     * Get the current gross debt balance for the customer.
     */
    public function grossDebtBalance(): float
    {
        $balance = $this->relationLoaded('debtMovements')
            ? $this->debtMovements->first()?->balance_after
            : $this->debtMovements()->value('balance_after');

        return round((float) ($balance ?? 0), 2);
    }

    /**
     * Get the portion of reward balance that remains available after covering debt.
     */
    public function availableRewardBalance(): float
    {
        return round(max((float) $this->reward_balance - $this->grossDebtBalance(), 0), 2);
    }

    /**
     * Get the effective debt balance after automatically applying reward balance.
     */
    public function debtBalance(): float
    {
        return round(max($this->grossDebtBalance() - (float) $this->reward_balance, 0), 2);
    }

    /**
     * Determine if the customer currently owes money after offsets.
     */
    public function hasDebt(): bool
    {
        return $this->debtBalance() > 0;
    }

    /**
     * Resolve the customer's Tonalpohualli reading.
     *
     * Returns an array compatible with the existing view mapping:
     * ['tonalli' => 'Quiahuitl', 'nahua' => 'Quiahuitl', 'espanol' => 'Lluvia', 'trecena' => '1-Tochtli (Conejo)', ...]
     *
     * @return array<string, string|int>
     */
    public function tonalpohualli(): array
    {
        if (! $this->birthday) {
            return [];
        }

        $calendar = app(TonalpohualliCalendar::class);

        $data = $calendar->resolve($this->birthday->toImmutable());
        $data['tonalli_display'] = $data['tonalli'] ?? null;
        $data['trecena_display'] = $data['trecena'] ?? null;

        // The calendar currently returns 'tonalli' with a leading coefficient like "12 - Quiahuitl".
        // For compact list display we strip the leading number and keep only the sign name.
        if (isset($data['tonalli']) && str_contains($data['tonalli'], ' - ')) {
            [$coef, $name] = explode(' - ', $data['tonalli'], 2);
            $data['tonalli'] = $name;
        }

        // The 'trecena' value is returned with a leading number like "1-Tochtli (Conejo)".
        // Remove the numeric prefix so the view shows only the name and translation.
        if (isset($data['trecena']) && str_contains($data['trecena'], '-')) {
            [, $trecenaName] = explode('-', $data['trecena'], 2);
            $data['trecena'] = trim($trecenaName);
        }

        // Try to resolve an icon file for the tonalli and trecena based on nahua name.
        $iconsPath = public_path('icons');

        if (! empty($data['nahua']) && is_dir($iconsPath)) {
            $slug = Str::of($data['nahua'])->lower()->replace(' ', '-')->__toString();

            $matches = glob($iconsPath."/*{$slug}*.svg");

            if ($matches !== false && count($matches) > 0) {
                $data['icon'] = asset('icons/'.basename($matches[0]));
            }
        }

        if (! empty($data['trecena'])) {
            // Extract the trecena sign name (e.g., "Tochtli" from "Tochtli (Conejo)")
            $trecenaSign = trim(explode(' ', $data['trecena'])[0]);
            $trecenaSlug = Str::of($trecenaSign)->lower()->replace(' ', '-')->__toString();

            $trecenaMatches = glob($iconsPath."/*{$trecenaSlug}*.svg");

            if ($trecenaMatches !== false && count($trecenaMatches) > 0) {
                $data['trecena_icon'] = asset('icons/'.basename($trecenaMatches[0]));
            }
        }

        return $data;
    }
}
