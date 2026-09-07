<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta name="csrf-token" content="{{ csrf_token() }}" />

<title>
    {{ filled($title ?? null) ? $title.' - '.config('app.name', 'Laravel') : config('app.name', 'Laravel') }}
</title>

<link rel="icon" type="image/png" href="/favicon-96x96.png" sizes="96x96" />
<link rel="icon" type="image/svg+xml" href="/favicon.svg" />
<link rel="shortcut icon" href="/favicon.ico" />
<link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png" />
<meta name="theme-color" content="{{ ($whatsappPwa ?? false) ? '#075e54' : '#ffffff' }}" />
<meta name="apple-mobile-web-app-capable" content="yes" />
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent" />
<meta name="apple-mobile-web-app-title" content="{{ ($whatsappPwa ?? false) ? 'WhatsApp 20Trece' : '20Trece' }}" />
<link rel="manifest" href="{{ ($whatsappPwa ?? false) ? '/whatsapp-pwa.webmanifest' : '/site.webmanifest' }}" />

@fonts

@vite(['resources/css/app.css', 'resources/js/app.js'])
@if ($whatsappPwa ?? false)
    @vite('resources/js/whatsapp-pwa.js')
@endif
@fluxAppearance
