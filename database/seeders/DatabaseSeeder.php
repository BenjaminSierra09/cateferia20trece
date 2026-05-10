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
        $branch = Branch::factory()->create([
            'name' => 'Matriz Centro',
            'city' => 'San Miguel de Allende',
        ]);

        $sizes = collect([
            ['name' => 'Chico', 'capacity_label' => '8 oz', 'capacity_ounces' => 8],
            ['name' => 'Mediano', 'capacity_label' => '12 oz', 'capacity_ounces' => 12],
            ['name' => 'Grande', 'capacity_label' => '16 oz', 'capacity_ounces' => 16],
        ])->map(fn (array $size) => Size::factory()->create($size));

        $category = BeverageCategory::factory()->create([
            'name' => 'Café caliente',
            'slug' => 'cafe-caliente',
        ]);

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

        $almond = CustomizationOption::factory()->create([
            'customization_type_id' => $milkType->id,
            'name' => 'Almendra',
            'price' => 12,
        ]);

        $shot = CustomizationOption::factory()->create([
            'customization_type_id' => $extraType->id,
            'name' => 'Shot extra',
            'price' => 18,
        ]);

        $latte = Beverage::factory()->create([
            'beverage_category_id' => $category->id,
            'name' => 'Latte',
            'slug' => Str::slug('Latte'),
            'base_price' => 58,
        ]);

        foreach ($sizes as $size) {
            $latte->sizePrices()->create([
                'size_id' => $size->id,
                'price' => match ($size->name) {
                    'Chico' => 58,
                    'Mediano' => 66,
                    default => 74,
                },
            ]);
        }

        $latte->customizationOptions()->attach([$almond->id, $shot->id]);

        User::factory()->create([
            'name' => 'Administrador Café 20Trece',
            'username' => 'admin',
            'email' => 'admin@20trece.test',
            'role' => UserRole::Admin,
            'branch_id' => $branch->id,
            // The `password` attribute is cast to `hashed` on the model,
            // so provide the plain password and let the model hash it.
            'password' => 'password',
            'is_active' => true,
        ]);

        Customer::factory()->count(5)->create();
    }
}
