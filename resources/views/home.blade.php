<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'The Center') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Styles / Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-white text-gray-900 antialiased" style="font-family: 'Inter', sans-serif;">
        <!-- Header with Language Switcher -->
        <header class="sticky top-0 z-50 w-full bg-white/95 backdrop-blur-sm border-b border-gray-200">
            <div class="max-w-6xl mx-auto px-6 lg:px-8 py-5 flex justify-between items-center">
                <h1 class="text-2xl lg:text-3xl font-bold text-gray-900">
                    {{ config('app.name', 'The Center') }}
                </h1>
                <x-language-switcher />
            </div>
        </header>

        <!-- Hero Section -->
        <section class="relative bg-gradient-to-br from-green-50 via-white to-green-50 py-20 lg:py-32">
            <div class="max-w-6xl mx-auto px-6 lg:px-8 text-center">
                <h2 class="text-4xl lg:text-6xl font-bold text-gray-900 mb-6 leading-tight">
                    Welcome to The Center
                </h2>
                <p class="text-xl lg:text-2xl text-gray-600 max-w-3xl mx-auto leading-relaxed">
                    Your destination for productive work, meaningful connections, and personal wellbeing
                </p>
            </div>
        </section>

        <!-- Main Content -->
        <main class="max-w-6xl mx-auto px-6 lg:px-8 py-16 lg:py-24">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-12">
                <!-- The Center Block -->
                <article class="group bg-white rounded-2xl shadow-sm hover:shadow-xl transition-all duration-300 overflow-hidden border border-gray-100">
                    <div class="p-8 lg:p-10 flex flex-col h-full">
                        <div class="flex-1">
                            <div class="w-16 h-16 rounded-xl mb-6 flex items-center justify-center" style="background: linear-gradient(135deg, rgb(140 198 62), rgb(120 178 42));">
                                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                </svg>
                            </div>
                            <h2 class="text-3xl lg:text-4xl font-bold mb-4 text-gray-900 transition-colors" style="transition: color 0.3s ease;">
                                {{ __('homepage.the_center.title') }}
                            </h2>
                            <p class="text-gray-600 text-lg leading-relaxed mb-8">
                                {{ __('homepage.the_center.description') }}
                            </p>
                        </div>
                        <div>
                            <a
                                href="#"
                                class="inline-flex items-center justify-center w-full px-8 py-4 text-white rounded-lg font-semibold transition-all duration-200 shadow-sm hover:shadow-md"
                                style="background-color: rgb(140 198 62);"
                                onmouseover="this.style.backgroundColor='rgb(120 178 42)'"
                                onmouseout="this.style.backgroundColor='rgb(140 198 62)'"
                            >
                                {{ __('homepage.the_center.cta') }}
                                <svg class="ml-2 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                                </svg>
                            </a>
                        </div>
                    </div>
                </article>

                <!-- The Light Center Block -->
                <article class="group bg-white rounded-2xl shadow-sm hover:shadow-xl transition-all duration-300 overflow-hidden border border-gray-100">
                    <div class="p-8 lg:p-10 flex flex-col h-full">
                        <div class="flex-1">
                            <div class="w-16 h-16 bg-gradient-to-br from-amber-500 to-orange-500 rounded-xl mb-6 flex items-center justify-center">
                                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                </svg>
                            </div>
                            <h2 class="text-3xl lg:text-4xl font-bold mb-4 text-gray-900 group-hover:text-orange-600 transition-colors">
                                {{ __('homepage.the_light_center.title') }}
                            </h2>
                            <p class="text-gray-600 text-lg leading-relaxed mb-8">
                                {{ __('homepage.the_light_center.description') }}
                            </p>
                        </div>
                        <div>
                            <a
                                href="#"
                                class="inline-flex items-center justify-center w-full px-8 py-4 bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-600 hover:to-orange-600 text-white rounded-lg font-semibold transition-all duration-200 shadow-sm hover:shadow-md"
                            >
                                {{ __('homepage.the_light_center.cta') }}
                                <svg class="ml-2 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                                </svg>
                            </a>
                        </div>
                    </div>
                </article>
            </div>
        </main>

        <!-- Footer -->
        <footer class="w-full bg-gray-50 border-t border-gray-200 mt-24">
            <div class="max-w-6xl mx-auto px-6 lg:px-8 py-12 lg:py-16">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-8">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 mb-4">{{ config('app.name', 'The Center') }}</h3>
                        <p class="text-gray-600 text-sm leading-relaxed">
                            Your destination for productive work, meaningful connections, and personal wellbeing.
                        </p>
                    </div>
                    @if(config('app.center_address') || config('app.center_phone'))
                    <div>
                        <h4 class="text-sm font-semibold text-gray-900 mb-4 uppercase tracking-wider">Contact</h4>
                        <div class="space-y-2 text-gray-600 text-sm">
                            @if(config('app.center_address'))
                                <p>{{ config('app.center_address') }}</p>
                            @endif
                            @if(config('app.center_phone'))
                                <p>{{ config('app.center_phone') }}</p>
                            @endif
                        </div>
                    </div>
                    @endif
                    <div>
                        <h4 class="text-sm font-semibold text-gray-900 mb-4 uppercase tracking-wider">Quick Links</h4>
                        <nav class="space-y-2">
                            <a href="#" class="block text-gray-600 text-sm transition-colors" style="transition: color 0.2s ease;" onmouseover="this.style.color='rgb(140 198 62)'" onmouseout="this.style.color=''">About Us</a>
                            <a href="#" class="block text-gray-600 text-sm transition-colors" style="transition: color 0.2s ease;" onmouseover="this.style.color='rgb(140 198 62)'" onmouseout="this.style.color=''">Services</a>
                            <a href="#" class="block text-gray-600 text-sm transition-colors" style="transition: color 0.2s ease;" onmouseover="this.style.color='rgb(140 198 62)'" onmouseout="this.style.color=''">Booking</a>
                            <a href="#" class="block text-gray-600 text-sm transition-colors" style="transition: color 0.2s ease;" onmouseover="this.style.color='rgb(140 198 62)'" onmouseout="this.style.color=''">Contact</a>
                        </nav>
                    </div>
                </div>
                <div class="pt-8 border-t border-gray-200">
                    <p class="text-center text-sm text-gray-500">
                        &copy; {{ date('Y') }} {{ config('app.name', 'The Center') }}. All rights reserved.
                    </p>
                </div>
            </div>
        </footer>
    </body>
</html>
