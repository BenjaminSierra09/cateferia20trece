<?php

use App\Ai\Tools\BrowseMenuTool;
use App\Ai\Tools\CafeInfoTool;
use App\Ai\Tools\CheckCustomerBalanceTool;
use App\Ai\Tools\ListBranchesTool;
use App\Ai\Tools\ListFavoriteBeveragesTool;
use App\Ai\Tools\PlaceOrderTool;
use App\Models\Beverage;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Services\CustomerFavoriteBeverageService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Laravel\Ai\Tools\Request;

it('reports the customer balance', function () {
    $customer = Customer::factory()->create(['name' => 'Juan', 'reward_balance' => 150]);

    $result = (string) (new CheckCustomerBalanceTool($customer))->handle(new Request([]));

    expect($result)->toContain('saldo_recompensas')
        ->and($result)->toContain('$150.00')
        ->and($result)->toContain('Juan');
});

it('explains when the customer has no favorite beverages yet', function () {
    $customer = Customer::factory()->create();

    $result = (string) (new ListFavoriteBeveragesTool($customer))->handle(new Request([]));

    expect($result)->toContain('todavía no tiene bebidas favoritas');
});

it('lists favorite beverages from the favorites service', function () {
    $customer = Customer::factory()->create();

    $service = Mockery::mock(CustomerFavoriteBeverageService::class);
    $service->shouldReceive('topForCustomer')
        ->once()
        ->andReturn(collect([[
            'beverage_name' => 'Latte',
            'beverage_image_url' => null,
            'total_quantity' => 5,
            'line_count' => 2,
            'last_ordered_at' => null,
            'top_size' => ['size_id' => 1, 'size_name' => 'Grande', 'capacity_label' => '16 oz', 'total_quantity' => 5],
            'frequent_customizations' => [
                ['customization_option_id' => 1, 'type' => 'Leche', 'name' => 'sin azúcar', 'selection_count' => 3],
            ],
        ]]));
    app()->instance(CustomerFavoriteBeverageService::class, $service);

    $result = (string) (new ListFavoriteBeveragesTool($customer))->handle(new Request([]));

    expect($result)->toContain('Latte')
        ->and($result)->toContain('Grande')
        ->and($result)->toContain('sin azúcar');
});

it('browses the menu with beverages and products', function () {
    Queue::fake();

    Beverage::factory()->create(['name' => 'Latte', 'slug' => 'latte']);
    Product::factory()->create(['name' => 'Medialuna', 'slug' => 'medialuna']);

    $result = (string) (new BrowseMenuTool)->handle(new Request([]));

    expect($result)->toContain('"bebidas"')
        ->and($result)->toContain('"productos"')
        ->and($result)->toContain('Latte')
        ->and($result)->toContain('Medialuna');
});

it('lists only branches that can receive orders', function () {
    Branch::factory()->create([
        'name' => 'Centro',
        'mercado_pago_is_active' => true,
        'mercado_pago_default_terminal_id' => 'TERM-1',
    ]);
    Branch::factory()->create([
        'name' => 'Norte',
        'mercado_pago_is_active' => false,
        'mercado_pago_default_terminal_id' => null,
    ]);

    $result = (string) (new ListBranchesTool)->handle(new Request([]));

    expect($result)->toContain('Centro')
        ->and($result)->not->toContain('Norte');
});

it('prints an order ticket without creating a sale', function () {
    Http::fake(['api.mercadopago.com/*' => Http::response(['id' => 'action_1'], 200)]);

    $customer = Customer::factory()->create(['name' => 'Juan']);
    $branch = Branch::factory()->create([
        'name' => 'Centro',
        'mercado_pago_is_active' => true,
        'mercado_pago_access_token' => 'TEST-TOKEN',
        'mercado_pago_default_terminal_id' => 'TERM-1',
        'mercado_pago_default_terminal_name' => 'Caja 1',
    ]);

    $result = (string) (new PlaceOrderTool($customer))->handle(new Request([
        'branch_id' => $branch->id,
        'items' => [
            ['name' => 'Latte', 'size' => 'Grande', 'quantity' => 2, 'modifications' => ['sin azúcar']],
        ],
        'note' => 'Para llevar',
    ]));

    expect($result)->toContain('Centro')
        ->and($result)->toContain('No es un cobro')
        ->and(Sale::count())->toBe(0);

    Http::assertSent(function ($request) {
        return str_contains($request->url(), '/terminals/v1/actions')
            && $request['type'] === 'print'
            && str_contains((string) $request['content'], 'Juan')
            && str_contains((string) $request['content'], 'Latte')
            && str_contains((string) $request['content'], 'sin azúcar');
    });
});

it('refuses to place an order for an unknown branch', function () {
    $customer = Customer::factory()->create();

    $result = (string) (new PlaceOrderTool($customer))->handle(new Request([
        'branch_id' => 999999,
        'items' => [['name' => 'Latte', 'quantity' => 1]],
    ]));

    expect($result)->toContain('No encontré esa sucursal');
});

it('refuses to place an empty order', function () {
    $customer = Customer::factory()->create();
    $branch = Branch::factory()->create([
        'mercado_pago_is_active' => true,
        'mercado_pago_access_token' => 'TEST-TOKEN',
        'mercado_pago_default_terminal_id' => 'TERM-1',
    ]);

    $result = (string) (new PlaceOrderTool($customer))->handle(new Request([
        'branch_id' => $branch->id,
        'items' => [],
    ]));

    expect($result)->toContain('no tiene productos');
});

it('provides branch hours and location', function () {
    Branch::factory()->create([
        'name' => 'Centro',
        'address' => 'Av. Principal 123',
        'operating_hours' => '07:00 - 21:00',
        'is_active' => true,
    ]);

    $result = (string) (new CafeInfoTool)->handle(new Request(['topic' => 'horarios']));

    expect($result)->toContain('Centro')
        ->and($result)->toContain('07:00 - 21:00')
        ->and($result)->toContain('Av. Principal 123');
});

it('provides the rewards program link', function () {
    $result = (string) (new CafeInfoTool)->handle(new Request(['topic' => 'recompensas']));

    expect($result)->toContain(route('public.rewards'));
});

it('explains ARCO rights with the official privacy notice and contact', function () {
    $result = (string) (new CafeInfoTool)->handle(new Request(['topic' => 'arco']));

    expect($result)->toContain(route('public.privacy'))
        ->and($result)->toContain((string) config('services.privacy.email'));
});

it('returns every information section by default', function () {
    $result = (string) (new CafeInfoTool)->handle(new Request([]));

    expect($result)->toContain('recompensas')
        ->and($result)->toContain('privacidad')
        ->and($result)->toContain('sucursales')
        ->and($result)->toContain('terminos');
});
