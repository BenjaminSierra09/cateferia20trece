<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['branch_id', 'user_id', 'work_session_id', 'period_start_at', 'cut_at', 'opening_cash_amount', 'counted_cash_amount', 'expected_cash_amount', 'difference_amount', 'cash_sales_total', 'manual_income_total', 'manual_expense_total', 'notes'])]
class CashRegisterCut extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'period_start_at' => 'datetime',
            'cut_at' => 'datetime',
            'opening_cash_amount' => 'decimal:2',
            'counted_cash_amount' => 'decimal:2',
            'expected_cash_amount' => 'decimal:2',
            'difference_amount' => 'decimal:2',
            'cash_sales_total' => 'decimal:2',
            'manual_income_total' => 'decimal:2',
            'manual_expense_total' => 'decimal:2',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function workSession(): BelongsTo
    {
        return $this->belongsTo(WorkSession::class);
    }
}
