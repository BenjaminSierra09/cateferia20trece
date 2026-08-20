<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\WhatsAppMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WhatsAppMediaController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, WhatsAppMessage $whatsappMessage): StreamedResponse
    {
        $user = $request->user();

        abort_unless($user instanceof User && $user->canManageWhatsApp(), 403);
        abort_unless(
            filled($whatsappMessage->media_path)
                && Storage::disk('local')->exists($whatsappMessage->media_path),
            404,
        );

        return Storage::disk('local')->response(
            $whatsappMessage->media_path,
            headers: ['Content-Type' => $whatsappMessage->media_mime_type ?? 'application/octet-stream'],
        );
    }
}
