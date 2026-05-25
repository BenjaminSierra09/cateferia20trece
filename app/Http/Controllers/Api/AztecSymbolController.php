<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\AztecSymbolResource;
use App\Models\AztecSymbol;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AztecSymbolController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $symbols = AztecSymbol::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return AztecSymbolResource::collection($symbols);
    }

    public function show(AztecSymbol $aztecSymbol): AztecSymbolResource
    {
        abort_if(! $aztecSymbol->is_active, 404);

        return new AztecSymbolResource($aztecSymbol);
    }
}
