<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ __('Welcome') }} - {{ config('app.name', 'Laravel') }}</title>

        <link rel="icon" type="image/png" href="/favicon-96x96.png" sizes="96x96" />
        <link rel="icon" type="image/svg+xml" href="/favicon.svg" />
        <link rel="shortcut icon" href="/favicon.ico" />
        <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png" />
        <meta name="apple-mobile-web-app-title" content="20Trece" />
        <link rel="manifest" href="/site.webmanifest" />
        @fonts

        <!-- Styles -->
        @vite('resources/css/app.css')
    </head>
    <body class="bg-white dark:bg-[#0a0a0a] text-[#1b1b18] dark:text-white min-h-screen flex flex-col">
        <!-- Header con Login -->
        @if (Route::has('login'))
            <header class="border-b border-gray-200 dark:border-[#3E3E3A]">
                <nav class="max-w-4xl mx-auto px-6 lg:px-8 py-4 flex items-center justify-between text-sm">
                    <a href="{{ route('home') }}" class="font-bold text-lg">20 TRECE</a>
                    @auth
                        <a href="{{ route('dashboard') }}" class="px-4 py-2 border border-[#1b1b18] dark:border-white rounded hover:bg-[#1b1b18] dark:hover:bg-white hover:text-white dark:hover:text-[#1b1b18] transition-colors">
                            Dashboard
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="px-4 py-2 border border-[#1b1b18] dark:border-white rounded hover:bg-[#1b1b18] dark:hover:bg-white hover:text-white dark:hover:text-[#1b1b18] transition-colors">
                            Admin
                        </a>
                    @endauth
                </nav>
            </header>
        @endif

        <!-- Main Content -->
        <main class="flex-1 max-w-4xl mx-auto w-full px-6 lg:px-8 py-16 lg:py-24">
            <!-- Hero Section -->
            <section class="text-center mb-20">
                <h1 class="text-5xl lg:text-6xl font-bold mb-6">Café Pluma</h1>
                <h2 class="text-xl text-gray-600 dark:text-gray-300 mb-4">de Altura Orgánico</h2>
                <p class="text-gray-600 dark:text-gray-400 text-lg">En grano o molido</p>
            </section>

            <!-- Quick Actions Grid -->
            <div class="grid md:grid-cols-2 gap-8 mb-20">
                <!-- Menu Section -->
                <div class="border border-gray-200 dark:border-[#3E3E3A] rounded-lg p-8 text-center hover:border-[#8B6F47] dark:hover:border-[#d4a574] transition-colors">
                    <div class="w-12 h-12 mx-auto mb-4 text-[#8B6F47] flex items-center justify-center text-2xl">☕</div>
                    <h3 class="text-2xl font-bold mb-3">Menú</h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-6">Explora nuestras bebidas y opciones de personalización</p>
                    <a href="{{ route('login') }}" class="inline-block px-6 py-3 bg-[#1b1b18] dark:bg-white text-white dark:text-[#1b1b18] rounded hover:opacity-90 transition-opacity font-medium">
                        Ver menú completo
                    </a>
                </div>

                <!-- QR Section -->
                <div class="border border-gray-200 dark:border-[#3E3E3A] rounded-lg p-8 text-center hover:border-[#8B6F47] dark:hover:border-[#d4a574] transition-colors">
                    <div class="w-12 h-12 mx-auto mb-4 text-[#8B6F47] flex items-center justify-center text-2xl">🎁</div>
                    <h3 class="text-2xl font-bold mb-3">Mi QR</h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-6">Escanea tu código para ver tus puntos de recompensa</p>
                    <a href="{{ route('login') }}" class="inline-block px-6 py-3 bg-[#1b1b18] dark:bg-white text-white dark:text-[#1b1b18] rounded hover:opacity-90 transition-opacity font-medium">
                        Mis puntos
                    </a>
                </div>
            </div>

            <!-- Contact Section -->
            <section class="mb-20">
                <h2 class="text-3xl font-bold mb-12 text-center">Visitanos</h2>
                <div class="grid md:grid-cols-3 gap-8 mb-12">
                    <!-- Address -->
                    <div class="text-center">
                        <div class="text-4xl mb-4">📍</div>
                        <h3 class="font-bold mb-2">Dirección</h3>
                        <p class="text-gray-600 dark:text-gray-400 text-sm leading-relaxed">
                            Juárez 21<br>
                            Zona Centro<br>
                            37700 San Miguel de Allende, Gto.
                        </p>
                    </div>

                    <!-- Phone -->
                    <div class="text-center">
                        <div class="text-4xl mb-4">☎️</div>
                        <h3 class="font-bold mb-2">Teléfono</h3>
                        <a href="tel:+524151194612" class="text-[#8B6F47] hover:underline">
                            415 119 4612
                        </a>
                    </div>

                    <!-- Hours -->
                    <div class="text-center">
                        <div class="text-4xl mb-4">⏰</div>
                        <h3 class="font-bold mb-2">Horario</h3>
                        <p class="text-gray-600 dark:text-gray-400 text-sm">
                            Lunes a Domingo<br>
                            6:30 a.m. – 10 p.m.<br>
                            <span class="text-xs text-gray-500">*Horarios pueden variar</span>
                        </p>
                    </div>
                </div>
            </section>

            <!-- Social Links -->
            <section class="mb-12">
                <h2 class="text-3xl font-bold mb-8 text-center">Síguenos</h2>
                <div class="flex flex-wrap justify-center gap-6">
                    <a href="https://www.instagram.com/20trecesma/" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 px-6 py-3 border border-gray-300 dark:border-[#3E3E3A] rounded hover:bg-gray-50 dark:hover:bg-[#1a1a1a] transition-colors">
                        <span class="text-xl">📷</span>
                        Instagram
                    </a>
                    <a href="https://www.facebook.com/p/Cafeter%C3%ADa-20-Trece-61554300671726/" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 px-6 py-3 border border-gray-300 dark:border-[#3E3E3A] rounded hover:bg-gray-50 dark:hover:bg-[#1a1a1a] transition-colors">
                        <span class="text-xl">👍</span>
                        Facebook
                    </a>
                    <a href="https://www.tripadvisor.com.mx/Restaurant_Review-g151932-d27092190-Reviews-Cafeteria_20trece-San_Miguel_de_Allende_Central_Mexico_and_Gulf_Coast.html" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 px-6 py-3 border border-gray-300 dark:border-[#3E3E3A] rounded hover:bg-gray-50 dark:hover:bg-[#1a1a1a] transition-colors">
                        <span class="text-xl">⭐</span>
                        TripAdvisor
                    </a>
                    <a href="https://search.google.com/local/writereview?placeid=ChIJ-YDfJtlRK4QRi4sqs3hbzBA" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 px-6 py-3 border border-gray-300 dark:border-[#3E3E3A] rounded hover:bg-gray-50 dark:hover:bg-[#1a1a1a] transition-colors">
                        <span class="text-xl">🔍</span>
                        Google
                    </a>
                </div>
            </section>

            <!-- Footer CTA -->
            <section class="text-center py-12 border-t border-gray-200 dark:border-[#3E3E3A]">
                <p class="text-gray-600 dark:text-gray-400 mb-6">¿Eres cliente frecuente?</p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="{{ route('login') }}" class="px-8 py-3 bg-[#1b1b18] dark:bg-white text-white dark:text-[#1b1b18] rounded hover:opacity-90 transition-opacity font-medium">
                        Inicia sesión
                    </a>
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="px-8 py-3 border-2 border-[#1b1b18] dark:border-white text-[#1b1b18] dark:text-white rounded hover:bg-[#1b1b18] dark:hover:bg-white hover:text-white dark:hover:text-[#1b1b18] transition-colors font-medium">
                            Crear cuenta
                        </a>
                    @endif
                </div>
            </section>
        </main>

        <!-- Footer -->
        <footer class="border-t border-gray-200 dark:border-[#3E3E3A] bg-gray-50 dark:bg-[#0a0a0a]">
            <div class="max-w-4xl mx-auto px-6 lg:px-8 py-8 text-center text-sm text-gray-600 dark:text-gray-400">
                <p>© 2026 Café 20Trece. Todos los derechos reservados.</p>
            </div>
        </footer>
    </body>
</html>
