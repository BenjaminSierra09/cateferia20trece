<?php

namespace App\Support;

use App\Models\Customer;

class CustomerPhoneMatcher
{
    /**
     * Reduce a phone number to digits only.
     */
    public function normalize(?string $phone): string
    {
        return preg_replace('/\D+/', '', (string) $phone) ?? '';
    }

    /**
     * Find an active customer whose stored phone matches the given number.
     *
     * Matching is exact on the normalized digits, falling back to the trailing
     * national digits (last 10) so country-code and mobile-prefix differences
     * (e.g. "521..." from WhatsApp vs a stored "+52...") still resolve.
     */
    public function find(?string $phone): ?Customer
    {
        $normalized = $this->normalize($phone);

        if (strlen($normalized) < 7) {
            return null;
        }

        $tail = substr($normalized, -10);

        return Customer::query()
            ->where('is_active', true)
            ->where('phone', 'like', '%'.$tail.'%')
            ->get()
            ->first(function (Customer $customer) use ($normalized, $tail): bool {
                $candidate = $this->normalize($customer->phone);

                return $candidate === $normalized
                    || ($candidate !== '' && substr($candidate, -10) === $tail);
            });
    }
}
