<?php

namespace Database\Factories;

use App\Enums\MeasurementUnit;
use App\Models\InventoryItem;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<InventoryItem>
 */
class InventoryItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = ucfirst(fake()->unique()->words(2, true));

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1000, 999999),
            'unit' => fake()->randomElement(MeasurementUnit::cases()),
            'category' => fake()->randomElement(['Lácteos', 'Café', 'Desechables', 'Jarabes', 'Otros']),
            'is_active' => true,
        ];
    }
}
