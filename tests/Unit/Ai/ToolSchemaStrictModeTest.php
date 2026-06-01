<?php

use App\Ai\Tools\BrowseMenuTool;
use App\Ai\Tools\CafeInfoTool;
use App\Ai\Tools\CheckCustomerBalanceTool;
use App\Ai\Tools\ListBranchesTool;
use App\Ai\Tools\ListFavoriteBeveragesTool;
use App\Ai\Tools\PlaceOrderTool;
use App\Models\Customer;
use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\ObjectSchema;

/**
 * Compile a tool's parameters exactly like the OpenAI gateway's MapsTools::mapTool().
 *
 * @return array<string, mixed>
 */
function compiledToolParameters(Tool $tool): array
{
    $schema = $tool->schema(new JsonSchemaTypeFactory);

    $schemaArray = filled($schema)
        ? (new ObjectSchema($schema))->toSchema()
        : [];

    return [
        'type' => 'object',
        'properties' => $schemaArray['properties'] ?? [],
        'required' => $schemaArray['required'] ?? [],
        'additionalProperties' => false,
    ];
}

/**
 * Collect OpenAI strict-mode violations: any object property missing from "required",
 * or any object node that does not disable additional properties. Recurses into nested
 * objects and array item schemas.
 *
 * @param  array<string, mixed>  $node
 * @return array<int, string>
 */
function collectStrictViolations(array $node, string $path = ''): array
{
    $violations = [];
    $type = $node['type'] ?? null;
    $isObject = $type === 'object' || (is_array($type) && in_array('object', $type, true));

    if ($isObject) {
        $properties = (array) ($node['properties'] ?? []);

        if ($properties !== []) {
            $required = $node['required'] ?? [];

            foreach (array_keys($properties) as $key) {
                if (! in_array($key, $required, true)) {
                    $violations[] = trim($path.'.'.$key, '.');
                }
            }

            if (($node['additionalProperties'] ?? null) !== false) {
                $violations[] = ($path === '' ? 'root' : $path).' (additionalProperties != false)';
            }

            foreach ($properties as $key => $child) {
                if (is_array($child)) {
                    $violations = array_merge($violations, collectStrictViolations($child, trim($path.'.'.$key, '.')));
                }
            }
        }
    }

    if (is_array($node['items'] ?? null)) {
        $violations = array_merge($violations, collectStrictViolations($node['items'], trim($path.'[]', '.')));
    }

    return $violations;
}

it('compiles strict-mode-valid OpenAI schemas for every WhatsApp concierge tool', function () {
    $customer = new Customer;

    $tools = [
        new BrowseMenuTool,
        new CafeInfoTool,
        new ListBranchesTool,
        new CheckCustomerBalanceTool($customer),
        new ListFavoriteBeveragesTool($customer),
        new PlaceOrderTool($customer),
    ];

    foreach ($tools as $tool) {
        $violations = collectStrictViolations(
            compiledToolParameters($tool),
            class_basename($tool),
        );

        expect($violations)->toBe([]);
    }
});
