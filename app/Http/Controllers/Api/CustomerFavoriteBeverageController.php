<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CustomerFavoriteBeverageResource;
use App\Models\Customer;
use App\Services\CustomerFavoriteBeverageService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CustomerFavoriteBeverageController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(
        Request $request,
        Customer $customer,
        CustomerFavoriteBeverageService $customerFavoriteBeverageService,
    ): AnonymousResourceCollection {
        return CustomerFavoriteBeverageResource::collection(
            $customerFavoriteBeverageService->topForCustomer($customer),
        );
    }
}
