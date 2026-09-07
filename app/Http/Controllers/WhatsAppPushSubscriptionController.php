<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeleteWhatsAppPushSubscriptionRequest;
use App\Http\Requests\StoreWhatsAppPushSubscriptionRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class WhatsAppPushSubscriptionController extends Controller
{
    public function store(StoreWhatsAppPushSubscriptionRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $validated = $request->validated();

        $user->updatePushSubscription(
            endpoint: $validated['endpoint'],
            key: $validated['keys']['p256dh'],
            token: $validated['keys']['auth'],
            contentEncoding: $validated['content_encoding'],
        );

        return response()->json(['subscribed' => true], Response::HTTP_CREATED);
    }

    public function destroy(DeleteWhatsAppPushSubscriptionRequest $request): Response
    {
        /** @var User $user */
        $user = $request->user();
        $user->deletePushSubscription($request->validated('endpoint'));

        return response()->noContent();
    }
}
