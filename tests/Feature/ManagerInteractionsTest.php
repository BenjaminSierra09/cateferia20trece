<?php

use App\Livewire\Beverages\Manager as BeverageManager;
use App\Livewire\Branches\Manager as BranchManager;
use App\Livewire\Customers\Manager as CustomerManager;
use App\Livewire\Products\Manager as ProductManager;
use App\Livewire\Reports\Overview as ReportsOverview;
use App\Livewire\Team\Manager as TeamManager;
use App\Models\Beverage;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\CustomerQrCode;
use App\Models\Product;
use App\Models\User;
use App\Support\InitialIndexViewModeResolver;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

test('branch manager can select visible rows and bulk update their status', function () {
    Branch::factory()->count(2)->create(['is_active' => true]);

    $component = Livewire::test(BranchManager::class)
        ->call('togglePageSelection');

    expect($component->get('selectedBranchIds'))->toHaveCount(2);
    expect($component->get('selectPage'))->toBeTrue();

    $component->call('deactivateSelected');

    expect(Branch::query()->where('is_active', false)->count())->toBe(2);
    expect($component->get('selectedBranchIds'))->toBe([]);
});

test('beverage manager supports grid mode and bulk deactivate', function () {
    Beverage::factory()->count(2)->create(['is_active' => true]);

    $component = Livewire::test(BeverageManager::class)
        ->set('viewMode', 'grid')
        ->call('togglePageSelection');

    $component->assertSet('viewMode', 'grid');
    expect($component->get('selectedBeverageIds'))->toHaveCount(2);

    $component->call('deactivateSelected');

    expect(Beverage::query()->where('is_active', false)->count())->toBe(2);
});

test('customer manager can select visible rows and bulk deactivate', function () {
    $customers = Customer::factory()->count(2)->create(['is_active' => true]);

    $component = Livewire::test(CustomerManager::class)
        ->call('togglePageSelection');

    expect($component->get('selectedCustomerIds'))->toHaveCount(2);

    $component->call('deactivateSelected');

    $deactivatedCustomer = Customer::query()->findOrFail($customers->first()->id);

    expect(Customer::query()->where('is_active', false)->count())->toBe(2);
    expect($deactivatedCustomer->name)->toContain('Cliente desactivado');
    expect(CustomerQrCode::query()->where('customer_id', $customers->first()->id)->where('is_active', true)->exists())
        ->toBeFalse();
});

test('customer manager can resend the welcome whatsapp message', function () {
    config()->set('services.evolution.api_url', 'https://evolution.benjaminsierra.com');
    config()->set('services.evolution.api_key', 'test-api-key');
    config()->set('services.evolution.instance_id', 'San Miguel Live');

    Http::preventStrayRequests();
    Http::fake([
        'https://evolution.benjaminsierra.com/message/sendMedia/*' => Http::response(['status' => 'PENDING'], 201),
        'https://evolution.benjaminsierra.com/message/sendText/*' => Http::response(['status' => 'PENDING'], 201),
    ]);

    $customer = Customer::factory()->create([
        'name' => 'Benjamin Sierra',
        'phone' => '+524151234567',
    ]);

    $qrCode = CustomerQrCode::query()->where('customer_id', $customer->id)->firstOrFail();

    Livewire::test(CustomerManager::class)
        ->call('sendWelcomeMessage', $customer->id);

    Http::assertSentCount(4);
    Http::assertSent(function (Request $request) use ($customer): bool {
        return str_contains($request->url(), '/message/sendMedia/')
            && $request['number'] === '524151234567'
            && str_contains($request['caption'], $customer->name);
    });
    Http::assertSent(function (Request $request) use ($qrCode): bool {
        return str_contains($request->url(), '/message/sendText/')
            && str_contains($request['text'], route('public.qr.show', ['uuid' => $qrCode->uuid]));
    });
});

test('product manager supports grid mode', function () {
    Product::factory()->count(2)->create();

    Livewire::test(ProductManager::class)
        ->set('viewMode', 'grid')
        ->assertSet('viewMode', 'grid')
        ->assertSee('Productos');
});

test('product manager table supports flux sortable columns', function () {
    Product::factory()->create(['name' => 'Zarzamora', 'base_price' => 20]);
    Product::factory()->create(['name' => 'Avena', 'base_price' => 40]);

    Livewire::test(ProductManager::class)
        ->call('sort', 'name')
        ->assertSet('sortBy', 'name')
        ->assertSet('sortDirection', 'asc')
        ->assertSeeInOrder(['Avena', 'Zarzamora'])
        ->call('sort', 'name')
        ->assertSet('sortDirection', 'desc')
        ->assertSeeInOrder(['Zarzamora', 'Avena']);
});

test('team manager supports grid mode', function () {
    User::factory()->count(2)->create();

    Livewire::test(TeamManager::class)
        ->set('viewMode', 'grid')
        ->assertSet('viewMode', 'grid')
        ->assertSee('Colaboradores');
});

test('reports overview can switch between visual and detail modes', function () {
    Livewire::test(ReportsOverview::class)
        ->set('presentationMode', 'detail')
        ->assertSet('presentationMode', 'detail')
        ->assertSee('Bebidas destacadas');
});

test('initial index view mode resolver chooses grid on mobile and list on desktop', function () {
    $resolver = app(InitialIndexViewModeResolver::class);

    $mobileRequest = HttpRequest::create('/dashboard/customers', 'GET', server: [
        'HTTP_USER_AGENT' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148',
    ]);
    $desktopRequest = HttpRequest::create('/dashboard/customers', 'GET', server: [
        'HTTP_USER_AGENT' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 15_0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36',
    ]);

    expect($resolver->resolve($mobileRequest))->toBe('grid')
        ->and($resolver->resolve($desktopRequest))->toBe('list');
});

test('initial index view mode resolver keeps the requested query mode', function () {
    $resolver = app(InitialIndexViewModeResolver::class);

    $request = HttpRequest::create('/dashboard/customers', 'GET', [
        'view' => 'list',
    ], server: [
        'HTTP_USER_AGENT' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148',
    ]);

    expect($resolver->resolve($request))->toBe('list');
});

test('explicit view query keeps the requested mode', function () {
    request()->headers->set(
        'User-Agent',
        'Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148',
    );

    Livewire::withQueryParams(['view' => 'list'])->test(CustomerManager::class)
        ->assertSet('viewMode', 'list');
});
