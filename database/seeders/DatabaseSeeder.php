<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Beverage;
use App\Models\BeverageCategory;
use App\Models\Branch;
use App\Models\CustomizationOption;
use App\Models\CustomizationType;
use App\Models\Size;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create branches
        $branchCentro = Branch::factory()->create([
            'name' => 'Matriz Centro',
            'city' => 'San Miguel de Allende',
        ]);

        $branchCanal22 = Branch::factory()->create([
            'name' => 'Sucursal Canal 22',
            'city' => 'San Miguel de Allende',
        ]);

        // Create sizes
        $sizes = collect([
            ['name' => 'Mini', 'capacity_label' => '60 ml', 'capacity_ounces' => 2.03],
            ['name' => 'Chico', 'capacity_label' => '8 oz', 'capacity_ounces' => 8],
            ['name' => 'Mediano', 'capacity_label' => '12 oz', 'capacity_ounces' => 12],
            ['name' => 'Grande', 'capacity_label' => '16 oz', 'capacity_ounces' => 16],
        ])->mapWithKeys(fn (array $size) => [
            $size['name'] => Size::factory()->create($size),
        ]);

        $categories = collect([
            ['name' => 'Cafés pequeños / especiales', 'slug' => 'cafes-pequenos-especiales'],
            ['name' => 'Especiales medianos', 'slug' => 'especiales-medianos'],
            ['name' => 'Bebidas básicas', 'slug' => 'bebidas-basicas'],
            ['name' => 'Lattes y chocolate', 'slug' => 'lattes-y-chocolate'],
            ['name' => 'Saborizados', 'slug' => 'saborizados'],
            ['name' => 'Infusiones y frutales', 'slug' => 'infusiones-y-frutales'],
            ['name' => 'Métodos filtrados', 'slug' => 'metodos-filtrados'],
        ])->mapWithKeys(fn (array $category) => [
            $category['name'] => BeverageCategory::factory()->create($category),
        ]);

        $customizationTypes = collect([
            [
                'name' => 'Shots / sabores',
                'slug' => 'shots-sabores',
                'selection_mode' => 'multiple',
                'options' => [
                    ['name' => 'Shot de café', 'price' => 15],
                    ['name' => 'Chai', 'price' => 15],
                    ['name' => 'Taro', 'price' => 15],
                    ['name' => 'Matcha', 'price' => 15],
                    ['name' => 'Vainilla', 'price' => 15],
                    ['name' => 'Caramelo', 'price' => 15],
                    ['name' => 'Avellana', 'price' => 15],
                    ['name' => 'Pistache', 'price' => 15],
                    ['name' => 'Menta', 'price' => 15],
                    ['name' => 'Mazapán', 'price' => 15],
                    ['name' => 'Cajeta', 'price' => 15],
                    ['name' => 'Foam', 'price' => 15],
                    ['name' => 'Crema batida', 'price' => 15],
                    ['name' => 'Miel', 'price' => 15],
                    ['name' => 'Jarabe', 'price' => 15],
                ],
            ],
            [
                'name' => 'Leches',
                'slug' => 'leches',
                'selection_mode' => 'single',
                'options' => [
                    ['name' => 'Light', 'price' => 0],
                    ['name' => 'Avena', 'price' => 0],
                    ['name' => 'Soya', 'price' => 10],
                    ['name' => 'Almendra', 'price' => 15],
                ],
            ],
            [
                'name' => 'Boba / toppings',
                'slug' => 'boba-y-toppings',
                'selection_mode' => 'multiple',
                'options' => [
                    ['name' => 'Boba', 'price' => 20],
                    ['name' => 'Tapioca', 'price' => 20],
                    ['name' => 'Jellys', 'price' => 20],
                ],
            ],
            [
                'name' => 'Chocolates especiales',
                'slug' => 'chocolates-especiales',
                'selection_mode' => 'single',
                'options' => [
                    ['name' => '100% cacao', 'price' => 5],
                    ['name' => '70% cacao', 'price' => 5],
                    ['name' => 'Amargo', 'price' => 5],
                    ['name' => 'Dulce', 'price' => 0],
                    ['name' => 'Cardamomo', 'price' => 5],
                    ['name' => 'Especial', 'price' => 5],
                    ['name' => 'Oaxaqueño', 'price' => 0],
                    ['name' => 'San Isidro', 'price' => 0],
                    ['name' => 'Canela', 'price' => 0],
                    ['name' => 'Blanco', 'price' => 0],
                    ['name' => 'Masala', 'price' => 5],
                ],
            ],
        ])->mapWithKeys(fn (array $type) => [
            $type['name'] => $this->createCustomizationTypeWithOptions($type),
        ]);

        $allCustomizationOptionIds = $customizationTypes
            ->flatMap(fn (CustomizationType $customizationType) => $customizationType->options->pluck('id'))
            ->all();

        $beverages = [
            ['category' => 'Cafés pequeños / especiales', 'name' => 'Espresso', 'prices' => ['Mini' => 35]],
            ['category' => 'Cafés pequeños / especiales', 'name' => 'Machiato', 'prices' => ['Mini' => 35]],
            ['category' => 'Cafés pequeños / especiales', 'name' => 'Cortado', 'prices' => ['Mini' => 35]],
            ['category' => 'Cafés pequeños / especiales', 'name' => 'Tinto ch', 'prices' => ['Mini' => 35]],
            ['category' => 'Cafés pequeños / especiales', 'name' => 'Cubano', 'prices' => ['Mini' => 35]],
            ['category' => 'Especiales medianos', 'name' => 'Flat White', 'prices' => ['Chico' => 50]],
            ['category' => 'Especiales medianos', 'name' => 'Levanta muertos', 'prices' => ['Chico' => 50]],
            ['category' => 'Especiales medianos', 'name' => 'Long Black', 'prices' => ['Chico' => 50]],
            ['category' => 'Bebidas básicas', 'name' => 'Americano', 'prices' => ['Chico' => 35, 'Mediano' => 45, 'Grande' => 50]],
            ['category' => 'Bebidas básicas', 'name' => 'Mexicano', 'prices' => ['Chico' => 35, 'Mediano' => 45, 'Grande' => 50]],
            ['category' => 'Bebidas básicas', 'name' => 'Té', 'prices' => ['Chico' => 35, 'Mediano' => 45, 'Grande' => 50]],
            ['category' => 'Lattes y chocolate', 'name' => 'Latte', 'prices' => ['Chico' => 39, 'Mediano' => 59, 'Grande' => 69]],
            ['category' => 'Lattes y chocolate', 'name' => 'Capuchino', 'prices' => ['Chico' => 39, 'Mediano' => 59, 'Grande' => 69]],
            ['category' => 'Lattes y chocolate', 'name' => 'Lechero', 'prices' => ['Chico' => 39, 'Mediano' => 59, 'Grande' => 69]],
            ['category' => 'Lattes y chocolate', 'name' => 'Xocolatl', 'prices' => ['Chico' => 39, 'Mediano' => 59, 'Grande' => 69]],
            ['category' => 'Lattes y chocolate', 'name' => 'Chocolate', 'prices' => ['Chico' => 39, 'Mediano' => 59, 'Grande' => 69]],
            ['category' => 'Saborizados', 'name' => 'Vainilla Latte', 'prices' => ['Chico' => 45, 'Mediano' => 65, 'Grande' => 75]],
            ['category' => 'Saborizados', 'name' => 'Caramel Latte', 'prices' => ['Chico' => 45, 'Mediano' => 65, 'Grande' => 75]],
            ['category' => 'Saborizados', 'name' => 'Chococafé', 'prices' => ['Chico' => 45, 'Mediano' => 65, 'Grande' => 75]],
            ['category' => 'Saborizados', 'name' => 'Moka', 'prices' => ['Chico' => 45, 'Mediano' => 65, 'Grande' => 75]],
            ['category' => 'Saborizados', 'name' => 'Chai', 'prices' => ['Chico' => 45, 'Mediano' => 65, 'Grande' => 75]],
            ['category' => 'Saborizados', 'name' => 'Taro', 'prices' => ['Chico' => 45, 'Mediano' => 65, 'Grande' => 75]],
            ['category' => 'Saborizados', 'name' => 'Matcha', 'prices' => ['Chico' => 45, 'Mediano' => 65, 'Grande' => 75]],
            ['category' => 'Infusiones y frutales', 'name' => 'Tisana', 'prices' => ['Mediano' => 55]],
            ['category' => 'Infusiones y frutales', 'name' => 'Flor de café', 'prices' => ['Mediano' => 55]],
            ['category' => 'Infusiones y frutales', 'name' => 'Cereza de café', 'prices' => ['Mediano' => 55]],
            ['category' => 'Infusiones y frutales', 'name' => 'Frutal (agua de sabor con café)', 'prices' => ['Mediano' => 55]],
            ['category' => 'Métodos filtrados', 'name' => 'Café Turco', 'prices' => ['Mediano' => 60]],
            ['category' => 'Métodos filtrados', 'name' => 'Moka pot', 'prices' => ['Mediano' => 60]],
            ['category' => 'Métodos filtrados', 'name' => 'V60', 'prices' => ['Mediano' => 70]],
            ['category' => 'Métodos filtrados', 'name' => 'Kalita', 'prices' => ['Mediano' => 70]],
            ['category' => 'Métodos filtrados', 'name' => 'Chemex', 'prices' => ['Mediano' => 80]],
            ['category' => 'Métodos filtrados', 'name' => 'AeroPress', 'prices' => ['Mediano' => 60]],
            ['category' => 'Métodos filtrados', 'name' => 'Prensa francesa', 'prices' => ['Mediano' => 70]],
            ['category' => 'Métodos filtrados', 'name' => 'Siphon japonés', 'prices' => ['Mediano' => 80]],
            ['category' => 'Métodos filtrados', 'name' => 'Origami', 'prices' => ['Mediano' => 70]],
            ['category' => 'Métodos filtrados', 'name' => 'Ahu', 'prices' => ['Mediano' => 60]],
            ['category' => 'Métodos filtrados', 'name' => 'Olla', 'prices' => ['Mediano' => 60]],
            ['category' => 'Métodos filtrados', 'name' => 'Calcetín', 'prices' => ['Mediano' => 60]],
            ['category' => 'Métodos filtrados', 'name' => 'Stagg', 'prices' => ['Mediano' => 70]],
            ['category' => 'Métodos filtrados', 'name' => 'Piedra', 'prices' => ['Mediano' => 70]],
            ['category' => 'Métodos filtrados', 'name' => 'Barro', 'prices' => ['Mediano' => 70]],
        ];

        foreach ($beverages as $bevData) {
            $beverage = Beverage::create([
                'beverage_category_id' => $categories[$bevData['category']]->id,
                'name' => $bevData['name'],
                'slug' => Str::slug($bevData['name']),
                'base_price' => array_values($bevData['prices'])[0],
                'is_active' => true,
            ]);

            foreach ($bevData['prices'] as $sizeName => $price) {
                $beverage->sizePrices()->create([
                    'size_id' => $sizes[$sizeName]->id,
                    'price' => $price,
                ]);
            }

            // Attach customization options to beverages
            $beverage->customizationOptions()->attach($allCustomizationOptionIds);
        }

        // Create users
        // Admin
        User::factory()->create([
            'name' => 'Administrador Café 20Trece',
            'username' => 'admin',
            'email' => 'admin@20trece.test',
            'role' => UserRole::Admin,
            'password' => 'password',
            'is_active' => true,
        ]);

    }

    /**
     * Create a customization type and its options.
     *
     * @param  array{name: string, slug: string, selection_mode: string, options: array<int, array{name: string, price: int}>}  $customizationTypeData
     */
    private function createCustomizationTypeWithOptions(array $customizationTypeData): CustomizationType
    {
        $options = $customizationTypeData['options'];

        unset($customizationTypeData['options']);

        $customizationType = CustomizationType::factory()->create($customizationTypeData);

        collect($options)->each(function (array $option) use ($customizationType): void {
            CustomizationOption::factory()->create([
                'customization_type_id' => $customizationType->id,
                'name' => $option['name'],
                'price' => $option['price'],
            ]);
        });

        return $customizationType->load('options');
    }
}
