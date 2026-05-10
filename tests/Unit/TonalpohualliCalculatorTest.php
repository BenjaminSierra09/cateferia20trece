<?php

use App\Support\TonalpohualliCalendar;
use Carbon\CarbonImmutable;

test('resolves the documented pivot date', function () {
    $calendar = new TonalpohualliCalendar;

    $result = $calendar->resolve(CarbonImmutable::parse('1996-02-09', 'UTC'));

    expect($result['tonalli'])->toBe('6 - Xochitl')
        ->and($result['nahua'])->toBe('Xochitl')
        ->and($result['trecena'])->toBe('1-Cuauhtli (Aguila)');
});

test('resolves a stable historical date before the pivot', function () {
    $calendar = new TonalpohualliCalendar;

    $result = $calendar->resolve(CarbonImmutable::parse('1995-12-31', 'UTC'));

    expect($result['coeficiente'])->toBeInt()
        ->and($result['nahua'])->not->toBeEmpty()
        ->and($result['deidad'])->not->toBeEmpty()
        ->and($result['cuerpo'])->not->toBeEmpty();
});
