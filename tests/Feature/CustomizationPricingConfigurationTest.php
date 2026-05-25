<?php

use App\Livewire\Customizations\OptionForm;
use App\Models\Branch;
use App\Models\BranchCustomizationSizePriceOverride;
use App\Models\CustomizationOption;
use App\Models\CustomizationOptionSizePrice;
use App\Models\CustomizationType;
use App\Models\Size;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

test('customization option form stores size prices and branch overrides', function () {
    Queue::fake();

    $user = User::factory()->create();
    $type = CustomizationType::factory()->create();
    $branch = Branch::factory()->create(['name' => 'Centro']);
    $small = Size::factory()->create(['name' => 'Chico', 'capacity_ounces' => 8]);
    $large = Size::factory()->create(['name' => 'Grande', 'capacity_ounces' => 16]);
    $option = CustomizationOption::factory()->create([
        'customization_type_id' => $type->id,
        'name' => 'Leche de almendras',
        'price' => 15,
    ]);

    CustomizationOptionSizePrice::query()->create([
        'customization_option_id' => $option->id,
        'size_id' => $small->id,
        'price' => 15,
    ]);

    Livewire::actingAs($user)
        ->test(OptionForm::class, ['customizationOption' => $option])
        ->set('activeTab', 'prices')
        ->set("size_prices.{$small->id}", 10)
        ->set("size_prices.{$large->id}", 20)
        ->set("branch_size_price_overrides.{$branch->id}.{$small->id}", 12)
        ->set("branch_size_price_overrides.{$branch->id}.{$large->id}", '')
        ->call('save');

    expect((float) CustomizationOptionSizePrice::query()
        ->where('customization_option_id', $option->id)
        ->where('size_id', $small->id)
        ->value('price'))->toBe(10.0);
    expect((float) CustomizationOptionSizePrice::query()
        ->where('customization_option_id', $option->id)
        ->where('size_id', $large->id)
        ->value('price'))->toBe(20.0);
    expect((float) BranchCustomizationSizePriceOverride::query()
        ->where('branch_id', $branch->id)
        ->where('customization_option_id', $option->id)
        ->where('size_id', $small->id)
        ->value('price'))->toBe(12.0);
    expect(BranchCustomizationSizePriceOverride::query()
        ->where('branch_id', $branch->id)
        ->where('customization_option_id', $option->id)
        ->where('size_id', $large->id)
        ->exists())->toBeFalse();
});
