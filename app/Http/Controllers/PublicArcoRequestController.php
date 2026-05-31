<?php

namespace App\Http\Controllers;

use App\Http\Requests\PublicArcoRequest;
use App\Mail\ArcoRequestMail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;

class PublicArcoRequestController extends Controller
{
    /**
     * Receive a public ARCO request (access, rectification, cancellation,
     * objection or consent revocation) and forward it to the data controller.
     */
    public function store(PublicArcoRequest $request): RedirectResponse
    {
        $data = $request->safe()->except(['website', 'identidad_consent']);

        Mail::to(config('services.privacy.email'))
            ->send(new ArcoRequestMail(
                nombre: $data['nombre'],
                email: $data['email'],
                telefono: $data['telefono'] ?? null,
                derechos: $data['derechos'],
                cuentaIdentificador: $data['cuenta_identificador'] ?? null,
                detalle: $data['detalle'],
            ));

        return redirect()
            ->route('public.privacy')
            ->withFragment('arco')
            ->with('arco_status', 'Recibimos tu solicitud de derechos ARCO. Te responderemos al correo proporcionado dentro de los plazos legales (máximo 20 días hábiles).');
    }
}
