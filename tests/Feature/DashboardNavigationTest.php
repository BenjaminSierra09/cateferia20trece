<?php

use App\Models\Beverage;
use App\Models\BeverageCategory;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\CustomizationOption;
use App\Models\CustomizationType;
use App\Models\Product;
use App\Models\Size;
use App\Models\User;

it('renders dashboard modules under the dashboard prefix', function (string $routeName) {
    $user = User::factory()->admin()->create();

    $this->actingAs($user)
        ->get(route($routeName))
        ->assertOk();
})->with([
    'dashboard',
    'dashboard.branches.index',
    'dashboard.categories.index',
    'dashboard.sizes.index',
    'dashboard.beverages.index',
    'dashboard.products.index',
    'dashboard.customizations.index',
    'dashboard.customizations.types.index',
    'dashboard.customizations.options.index',
    'dashboard.customers.index',
    'dashboard.sales.index',
    'dashboard.team.index',
    'dashboard.reports.index',
    'dashboard.reports.shifts',
]);

it('renders dashboard create screens under the dashboard prefix', function (string $routeName) {
    $user = User::factory()->admin()->create();

    $this->actingAs($user)
        ->get(route($routeName))
        ->assertOk();
})->with([
    'dashboard.branches.create',
    'dashboard.categories.create',
    'dashboard.sizes.create',
    'dashboard.beverages.create',
    'dashboard.products.create',
    'dashboard.customizations.types.create',
    'dashboard.customizations.options.create',
    'dashboard.customers.create',
    'dashboard.team.create',
]);

it('renders dashboard customer edit screen under the dashboard prefix', function () {
    $user = User::factory()->admin()->create();
    $customer = Customer::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard.customers.edit', $customer))
        ->assertOk();
});

it('renders dashboard edit screens under the dashboard prefix', function () {
    $branch = Branch::factory()->create();
    $user = User::factory()->admin()->create();
    $category = BeverageCategory::factory()->create();
    $size = Size::factory()->create();
    $beverage = Beverage::factory()->create(['beverage_category_id' => $category->id]);
    $product = Product::factory()->create();
    $customizationType = CustomizationType::factory()->create();
    $customizationOption = CustomizationOption::factory()->create(['customization_type_id' => $customizationType->id]);
    $teammate = User::factory()->employee()->create();

    $this->actingAs($user)->get(route('dashboard.branches.edit', $branch))->assertOk();
    $this->actingAs($user)->get(route('dashboard.categories.edit', $category))->assertOk();
    $this->actingAs($user)->get(route('dashboard.sizes.edit', $size))->assertOk();
    $this->actingAs($user)->get(route('dashboard.beverages.edit', $beverage))->assertOk();
    $this->actingAs($user)->get(route('dashboard.products.edit', $product))->assertOk();
    $this->actingAs($user)->get(route('dashboard.customizations.types.edit', $customizationType))->assertOk();
    $this->actingAs($user)->get(route('dashboard.customizations.options.edit', $customizationOption))->assertOk();
    $this->actingAs($user)->get(route('dashboard.team.edit', $teammate))->assertOk();
});

it('redirects legacy customization create route to customization types create', function () {
    $user = User::factory()->admin()->create();

    $this->actingAs($user)
        ->get(route('dashboard.customizations.create'))
        ->assertRedirect(route('dashboard.customizations.types.create'));
});

it('uses dashboard prefixes in generated urls', function () {
    expect(route('dashboard.branches.index'))->toContain('/dashboard/branches');
    expect(route('dashboard.branches.create'))->toContain('/dashboard/branches/create');
    expect(route('dashboard.beverages.index'))->toContain('/dashboard/beverages');
    expect(route('dashboard.products.index'))->toContain('/dashboard/products');
    expect(route('dashboard.customizations.types.index'))->toContain('/dashboard/customizations/types');
    expect(route('dashboard.customizations.options.index'))->toContain('/dashboard/customizations/options');
    expect(route('dashboard.customers.index'))->toContain('/dashboard/customers');
    expect(route('dashboard.customers.create'))->toContain('/dashboard/customers/create');
    expect(route('dashboard.sales.index'))->toContain('/dashboard/sales');
    expect(route('dashboard.reports.shifts'))->toContain('/dashboard/reports/shifts');
});
