<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Beverage;
use App\Models\BeverageCategory;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\CustomizationOption;
use App\Models\CustomizationType;
use App\Models\Size;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
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
            ['name' => 'Chico', 'capacity_label' => '8 oz', 'capacity_ounces' => 8],
            ['name' => 'Mediano', 'capacity_label' => '12 oz', 'capacity_ounces' => 12],
            ['name' => 'Grande', 'capacity_label' => '16 oz', 'capacity_ounces' => 16],
        ])->map(fn (array $size) => Size::factory()->create($size));

        // Create beverage category
        $category = BeverageCategory::factory()->create([
            'name' => 'Café caliente',
            'slug' => 'cafe-caliente',
        ]);

        // Create customization types
        $milkType = CustomizationType::factory()->create([
            'name' => 'Tipo de leche',
            'slug' => 'tipo-de-leche',
            'selection_mode' => 'single',
        ]);

        $extraType = CustomizationType::factory()->create([
            'name' => 'Extras',
            'slug' => 'extras',
            'selection_mode' => 'multiple',
        ]);

        // Create milk options
        $milkOptions = [
            'Entera' => 0,
            'Deslactosada' => 12,
            'Almendra' => 12,
            'Avena' => 12,
            'Coco' => 12,
        ];

        $milks = collect($milkOptions)->map(fn ($price, $name) => 
            CustomizationOption::factory()->create([
                'customization_type_id' => $milkType->id,
                'name' => $name,
                'price' => $price,
            ])
        );

        // Create extra options
        $extraOptions = [
            'Shot extra' => 18,
            'Miel' => 15,
            'Caramelo' => 15,
            'Vanilla' => 15,
            'Canela' => 10,
            'Chocolate en polvo' => 15,
        ];

        $extras = collect($extraOptions)->map(fn ($price, $name) => 
            CustomizationOption::factory()->create([
                'customization_type_id' => $extraType->id,
                'name' => $name,
                'price' => $price,
            ])
        );

        // Create beverages with prices based on menu
        $beverages = [
            ['name' => 'Espresso', 'prices' => [35, 35, 35]],
            ['name' => 'Machiato', 'prices' => [35, 35, 35]],
            ['name' => 'Cortado', 'prices' => [35, 35, 35]],
            ['name' => 'Americano', 'prices' => [35, 40, 45]],
            ['name' => 'Flat White', 'prices' => [45, 45, 45]],
            ['name' => 'Piccolo', 'prices' => [45, 45, 45]],
            ['name' => 'Latte', 'prices' => [39, 49, 59]],
            ['name' => 'Vanilla Latte', 'prices' => [45, 55, 65]],
            ['name' => 'Caramel Latte', 'prices' => [45, 55, 65]],
            ['name' => 'Cappuccino', 'prices' => [39, 49, 59]],
            ['name' => 'Chocolate', 'prices' => [39, 49, 59]],
            ['name' => 'Moxa', 'prices' => [45, 55, 65]],
            ['name' => 'Chai', 'prices' => [41, 51, 61]],
            ['name' => 'Dirty Chai', 'prices' => [49, 59, 69]],
            ['name' => 'Matcha', 'prices' => [41, 51, 61]],
            ['name' => 'Taro', 'prices' => [41, 51, 61]],
        ];

        foreach ($beverages as $bevData) {
            $beverage = Beverage::create([
                'beverage_category_id' => $category->id,
                'name' => $bevData['name'],
                'slug' => Str::slug($bevData['name']),
                'base_price' => $bevData['prices'][0],
                'is_active' => true,
            ]);

            foreach ($sizes as $size) {
                $sizeIndex = match ($size->name) {
                    'Chico' => 0,
                    'Mediano' => 1,
                    default => 2,
                };

                $beverage->sizePrices()->create([
                    'size_id' => $size->id,
                    'price' => $bevData['prices'][$sizeIndex],
                ]);
            }

            // Attach customization options to beverages
            $beverage->customizationOptions()->attach(
                $milks->pluck('id')->merge($extras->pluck('id'))
            );
        }

        // Create users
        // Admin
        User::factory()->create([
            'name' => 'Administrador Café 20Trece',
            'username' => 'admin',
            'email' => 'admin@20trece.test',
            'role' => UserRole::Admin,
            'branch_id' => $branchCentro->id,
            'password' => 'password',
            'is_active' => true,
        ]);

        // Manager for Centro
        User::factory()->create([
            'name' => 'Gerente Centro',
            'username' => 'gerente_centro',
            'email' => 'gerente.centro@20trece.test',
            'role' => UserRole::Manager,
            'branch_id' => $branchCentro->id,
            'password' => 'password',
            'is_active' => true,
        ]);

        // Manager for Canal 22
        User::factory()->create([
            'name' => 'Gerente Canal 22',
            'username' => 'gerente_canal22',
            'email' => 'gerente.canal22@20trece.test',
            'role' => UserRole::Manager,
            'branch_id' => $branchCanal22->id,
            'password' => 'password',
            'is_active' => true,
        ]);

        // Baristas for Centro
        for ($i = 1; $i <= 3; $i++) {
            User::factory()->create([
                'name' => "Barista Centro {$i}",
                'username' => "barista_centro_{$i}",
                'email' => "barista.centro.{$i}@20trece.test",
                'role' => UserRole::Barista,
                'branch_id' => $branchCentro->id,
                'password' => 'password',
                'is_active' => true,
            ]);
        }

        // Baristas for Canal 22
        for ($i = 1; $i <= 2; $i++) {
            User::factory()->create([
                'name' => "Barista Canal 22 {$i}",
                'username' => "barista_canal22_{$i}",
                'email' => "barista.canal22.{$i}@20trece.test",
                'role' => UserRole::Barista,
                'branch_id' => $branchCanal22->id,
                'password' => 'password',
                'is_active' => true,
            ]);
        }

        // Cashiers for Centro
        for ($i = 1; $i <= 2; $i++) {
            User::factory()->create([
                'name' => "Cajero Centro {$i}",
                'username' => "cajero_centro_{$i}",
                'email' => "cajero.centro.{$i}@20trece.test",
                'role' => UserRole::Cashier,
                'branch_id' => $branchCentro->id,
                'password' => 'password',
                'is_active' => true,
            ]);
        }

        // Cashier for Canal 22
        User::factory()->create([
            'name' => 'Cajero Canal 22',
            'username' => 'cajero_canal22',
            'email' => 'cajero.canal22@20trece.test',
            'role' => UserRole::Cashier,
            'branch_id' => $branchCanal22->id,
            'password' => 'password',
            'is_active' => true,
        ]);

        // Create customers
        Customer::factory()->count(15)->create();
    }
}
