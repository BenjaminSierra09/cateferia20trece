<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\ListBeverageCategories;
use App\Mcp\Tools\ListBeverages;
use App\Mcp\Tools\ListProducts;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('Cateferia Menu')]
#[Version('1.0.0')]
#[Instructions(<<<'MARKDOWN'
This server exposes the café's public menu and catalog as read-only tools.

Use it to answer questions about what is offered and how much it costs:

- `list_beverage_categories` — browse the beverage categories and how many drinks each holds.
- `list_beverages` — list drinks with their category, hot/cold temperature, base price, and per-size prices. Filter by category, temperature, or a name search.
- `list_products` — list food and other non-beverage items with their prices.

By default only items that are currently active (available on the menu) are returned. All prices are plain numeric amounts in the café's local currency. These tools never modify data.
MARKDOWN)]
class MenuServer extends Server
{
    protected array $tools = [
        ListBeverageCategories::class,
        ListBeverages::class,
        ListProducts::class,
    ];

    protected array $resources = [
        //
    ];

    protected array $prompts = [
        //
    ];
}
