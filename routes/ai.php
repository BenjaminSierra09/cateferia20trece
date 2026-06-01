<?php

use App\Mcp\Servers\MenuServer;
use Laravel\Mcp\Facades\Mcp;

// Public, read-only menu & catalog server. Reachable at POST /mcp/menu.
// Add ->middleware([...]) here (e.g. 'auth:sanctum') if access should be restricted.
Mcp::web('/mcp/menu', MenuServer::class);
