<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\WhatsAppMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class WhatsAppMediaController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, WhatsAppMessage $whatsappMessage): BinaryFileResponse
    {
        $user = $request->user();

        abort_unless($user instanceof User && $user->canManageWhatsApp(), 403);
        abort_unless(
            filled($whatsappMessage->media_path)
                && Storage::disk('local')->exists($whatsappMessage->media_path),
            404,
        );

        return response()->file(Storage::disk('local')->path($whatsappMessage->media_path), [
            'Accept-Ranges' => 'bytes',
            'Cache-Control' => 'private, no-store',
            'Content-Disposition' => 'inline',
            'Content-Type' => $whatsappMessage->media_mime_type ?? 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
