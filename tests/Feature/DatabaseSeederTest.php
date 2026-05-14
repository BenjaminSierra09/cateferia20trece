<?php

use App\Models\Beverage;
use App\Models\BeverageCategory;
use App\Models\CustomizationOption;
use App\Models\CustomizationType;
use App\Models\Size;
use Database\Seeders\DatabaseSeeder;

test('database seeder reflects the coffee menu catalog', function () {
    $this->seed(DatabaseSeeder::class);

    expect(Size::query()->pluck('name')->all())->toMatchArray([
        'Mini',
        'Chico',
        'Mediano',
        'Grande',
    ]);

    expect(BeverageCategory::query()->pluck('name')->all())->toMatchArray([
        'Cafés pequeños / especiales',
        'Especiales medianos',
        'Bebidas básicas',
        'Lattes y chocolate',
        'Saborizados',
        'Infusiones y frutales',
        'Métodos filtrados',
    ]);

    expect(Beverage::query()->count())->toBe(42);
    expect(CustomizationType::query()->count())->toBe(4);
    expect(CustomizationOption::query()->count())->toBe(33);

    expect((float) Beverage::query()->where('name', 'Espresso')->firstOrFail()->sizePrices()->value('price'))->toBe(35.0);
    expect((float) Beverage::query()->where('name', 'Americano')->firstOrFail()->sizePrices()->whereHas('size', fn ($query) => $query->where('name', 'Grande'))->value('price'))->toBe(50.0);
    expect((float) Beverage::query()->where('name', 'Tisana')->firstOrFail()->sizePrices()->value('price'))->toBe(55.0);
    expect((float) Beverage::query()->where('name', 'Café Turco')->firstOrFail()->sizePrices()->value('price'))->toBe(60.0);

    expect(CustomizationOption::query()->where('name', 'Almendra')->value('price'))->toBe(15);
    expect(CustomizationOption::query()->where('name', 'Boba')->value('price'))->toBe(20);
    expect(CustomizationOption::query()->where('name', 'Dulce')->value('price'))->toBe(0);
});
