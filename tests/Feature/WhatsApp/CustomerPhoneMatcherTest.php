<?php

use App\Models\Customer;
use App\Support\CustomerPhoneMatcher;

beforeEach(function () {
    $this->matcher = app(CustomerPhoneMatcher::class);
});

it('normalizes a phone number to digits only', function () {
    expect($this->matcher->normalize('+52 (418) 187-8244'))->toBe('524181878244');
});

it('matches a customer despite country code and mobile prefix differences', function () {
    $customer = Customer::factory()->create(['phone' => '+524181878244']);

    // WhatsApp delivers Mexican mobiles as 521 + 10 digits.
    expect($this->matcher->find('5214181878244')?->id)->toBe($customer->id);
});

it('matches on an exact normalized number', function () {
    $customer = Customer::factory()->create(['phone' => '4181878244']);

    expect($this->matcher->find('4181878244')?->id)->toBe($customer->id);
});

it('returns null when no active customer matches', function () {
    Customer::factory()->create(['phone' => '+524181878244']);

    expect($this->matcher->find('5219990001122'))->toBeNull();
});

it('does not match inactive customers', function () {
    Customer::factory()->create(['phone' => '+524181878244', 'is_active' => false]);

    expect($this->matcher->find('5214181878244'))->toBeNull();
});
