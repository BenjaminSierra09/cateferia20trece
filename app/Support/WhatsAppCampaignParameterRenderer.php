<?php

namespace App\Support;

use App\Models\Customer;
use Illuminate\Support\Str;

class WhatsAppCampaignParameterRenderer
{
    /**
     * @var array<int, string>
     */
    public const SUPPORTED_TOKENS = [
        '{{name}}',
        '{{first_name}}',
        '{{reward_balance}}',
        '{{reward_tier}}',
    ];

    /**
     * @param  array<int, string>  $templates
     * @return array<int, string>
     */
    public function render(array $templates, Customer $customer): array
    {
        $firstName = Str::of($customer->name)->trim()->before(' ')->toString();
        $replacements = [
            '{{name}}' => $customer->name,
            '{{first_name}}' => $firstName,
            '{{reward_balance}}' => number_format((float) $customer->reward_balance, 2, '.', ''),
            '{{reward_tier}}' => $customer->reward_tier?->label() ?? '',
        ];

        return array_map(
            fn (string $template): string => strtr($template, $replacements),
            $templates,
        );
    }

    /**
     * @param  array<int, string>  $templates
     * @return array<int, string>
     */
    public function unsupportedTokens(array $templates): array
    {
        preg_match_all('/{{\s*[^{}]+\s*}}/u', implode("\n", $templates), $matches);

        return collect($matches[0] ?? [])
            ->unique()
            ->reject(fn (string $token): bool => in_array($token, self::SUPPORTED_TOKENS, true))
            ->values()
            ->all();
    }
}
