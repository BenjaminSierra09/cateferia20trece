<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class WhatsAppPwaLoginController extends Controller
{
    public function __invoke(Request $request): View
    {
        $request->session()->put('url.intended', route('whatsapp.pwa'));

        return view('livewire.auth.login');
    }
}
