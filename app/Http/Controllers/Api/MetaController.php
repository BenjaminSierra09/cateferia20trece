<?php

namespace App\Http\Controllers\Api;

use App\Enums\PaymentMethod;
use App\Enums\CashMovementType;
use App\Enums\RewardTier;
use App\Enums\RewardTransactionType;
use App\Enums\SaleStatus;
use App\Enums\UserRole;
use App\Enums\WorkSessionStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MetaController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        return response()->json([
            'payment_methods' => collect(PaymentMethod::cases())->map(fn (PaymentMethod $method) => [
                'value' => $method->value,
                'label' => $method->label(),
            ])->values(),
            'cash_movement_types' => collect(CashMovementType::cases())->map(fn (CashMovementType $type) => [
                'value' => $type->value,
                'label' => $type->label(),
                'direction' => $type->direction(),
            ])->values(),
            'reward_tiers' => collect(RewardTier::cases())->map(fn (RewardTier $tier) => [
                'value' => $tier->value,
                'label' => $tier->label(),
                'percentage' => $tier->percentage(),
            ])->values(),
            'reward_transaction_types' => collect(RewardTransactionType::cases())->map(fn (RewardTransactionType $type) => [
                'value' => $type->value,
                'label' => $type->label(),
            ])->values(),
            'sale_statuses' => collect(SaleStatus::cases())->map(fn (SaleStatus $status) => [
                'value' => $status->value,
                'label' => $status->label(),
            ])->values(),
            'user_roles' => collect(UserRole::cases())->map(fn (UserRole $role) => [
                'value' => $role->value,
                'label' => $role->label(),
            ])->values(),
            'work_session_statuses' => collect(WorkSessionStatus::cases())->map(fn (WorkSessionStatus $status) => [
                'value' => $status->value,
                'label' => $status->label(),
            ])->values(),
        ]);
    }
}
