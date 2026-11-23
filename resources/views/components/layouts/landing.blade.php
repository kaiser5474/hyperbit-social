<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'HyperBit Social - ' . __('Open-Source Social Media Scheduler') }}</title>
    <meta name="description" content="{{ $description ?? __('Schedule and publish posts to Facebook and Instagram. Self-hostable, open-source Buffer alternative built for teams and businesses.') }}">

    <meta property="og:title" content="{{ $title ?? 'HyperBit Social' }}">
    <meta property="og:description" content="{{ $description ?? __('Schedule and publish posts to Facebook and Instagram.') }}">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ asset('images/og-image.png') }}">
    <meta property="og:locale" content="{{ app()->getLocale() == 'es' ? 'es_ES' : 'en_US' }}">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $title ?? 'HyperBit Social' }}">
    <meta name="twitter:description" content="{{ $description ?? __('Schedule and publish posts to Facebook and Instagram.') }}">
    <meta name="twitter:image" content="{{ asset('images/og-image.png') }}">

    <link rel="canonical" href="{{ url()->current() }}">

    @if(isset($jsonLd))
    <script type="application/ld+json">
    {!! json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}
    </script>
    @endif

    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-white">
    <nav class="fixed top-0 left-0 right-0 bg-white/90 backdrop-blur-sm z-50 border-b border-gray-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="{{ route('home') }}" class="flex items-center space-x-2">
                        <svg class="w-8 h-8" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M16 2L28.124 9v14L16 30 3.876 23V9L16 2z" fill="#4F46E5"/>
                            <path d="M10 10v12M22 10v12M10 16h12" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M24 6c1.5 1.5 2.5 3.5 2.5 5.5" stroke="#A5B4FC" stroke-width="1.5" stroke-linecap="round" opacity="0.8"/>
                            <path d="M22 4c2.5 2 4 5 4 8" stroke="#A5B4FC" stroke-width="1.5" stroke-linecap="round" opacity="0.6"/>
                        </svg>
                        <span class="font-bold text-xl text-gray-900">HyperBit Social</span>
                    </a>
                </div>

                <div class="hidden md:flex items-center space-x-6">
                    <a href="#features" class="text-gray-600 hover:text-gray-900 text-sm font-medium">{{ __('Features') }}</a>
                    <a href="#how-it-works" class="text-gray-600 hover:text-gray-900 text-sm font-medium">{{ __('How It Works') }}</a>
                    <a href="#pricing" class="text-gray-600 hover:text-gray-900 text-sm font-medium">{{ __('Pricing') }}</a>
                    <a href="#faq" class="text-gray-600 hover:text-gray-900 text-sm font-medium">{{ __('FAQ') }}</a>

                    <div class="flex items-center space-x-2 border-l pl-6 ml-2">
                        <a href="{{ route('lang.switch', 'es') }}" class="text-sm {{ app()->getLocale() == 'es' ? 'font-bold text-indigo-600' : 'text-gray-500 hover:text-gray-900' }}">ES</a>
                        <span class="text-gray-300">|</span>
                        <a href="{{ route('lang.switch', 'en') }}" class="text-sm {{ app()->getLocale() == 'en' ? 'font-bold text-indigo-600' : 'text-gray-500 hover:text-gray-900' }}">EN</a>
                    </div>

                    @auth
                        <a href="{{ route('dashboard') }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-white bg-indigo-600 hover:bg-indigo-700">
                            {{ __('Dashboard') }}
                        </a>
                    @else
                        <a href="{{ route('auth.facebook') }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-white bg-indigo-600 hover:bg-indigo-700">
                            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                            </svg>
                            {{ __('Login with Facebook') }}
                        </a>
                    @endauth
                </div>

                <div class="flex items-center md:hidden">
                    <button @click="$dispatch('toggle-mobile-menu')" class="text-gray-500 hover:text-gray-900">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <main class="pt-16">
        {{ $slot }}
    </main>

    <footer class="bg-gray-900 text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div class="col-span-1 md:col-span-2">
                    <div class="flex items-center space-x-2 mb-4">
                        <svg class="w-8 h-8" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M16 2L28.124 9v14L16 30 3.876 23V9L16 2z" fill="#818CF8"/>
                            <path d="M10 10v12M22 10v12M10 16h12" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M24 6c1.5 1.5 2.5 3.5 2.5 5.5" stroke="#C7D2FE" stroke-width="1.5" stroke-linecap="round" opacity="0.8"/>
                            <path d="M22 4c2.5 2 4 5 4 8" stroke="#C7D2FE" stroke-width="1.5" stroke-linecap="round" opacity="0.6"/>
                        </svg>
                        <span class="font-bold text-xl">HyperBit Social</span>
                    </div>
                    <p class="text-gray-400 text-sm mb-4">
                        {{ __('Open-source social media scheduler. Self-hostable Buffer alternative for Facebook and Instagram.') }}
                    </p>
                    <p class="text-gray-400 text-sm">
                        {{ __('Built by') }} <a href="https://hyperbitit.com" target="_blank" class="text-indigo-400 hover:text-indigo-300">HyperBit IT</a>
                    </p>
                </div>

                <div>
                    <h3 class="font-semibold mb-4">{{ __('Product') }}</h3>
                    <ul class="space-y-2 text-sm text-gray-400">
                        <li><a href="#features" class="hover:text-white">{{ __('Features') }}</a></li>
                        <li><a href="#pricing" class="hover:text-white">{{ __('Pricing') }}</a></li>
                        <li><a href="https://github.com/kaiser5474/hyperbit-social" target="_blank" class="hover:text-white">GitHub</a></li>
                    </ul>
                </div>

                <div>
                    <h3 class="font-semibold mb-4">{{ __('Legal') }}</h3>
                    <ul class="space-y-2 text-sm text-gray-400">
                        <li><a href="{{ route('privacy') }}" class="hover:text-white">{{ __('Privacy Policy') }}</a></li>
                        <li><a href="{{ route('terms') }}" class="hover:text-white">{{ __('Terms of Service') }}</a></li>
                        <li><a href="{{ route('data-deletion') }}" class="hover:text-white">{{ __('Data Deletion') }}</a></li>
                    </ul>
                </div>
            </div>

            <div class="border-t border-gray-800 mt-8 pt-8 text-center text-sm text-gray-400">
                <p>&copy; {{ date('Y') }} HyperBit IT. {{ __('All rights reserved.') }} | MIT License</p>
            </div>
        </div>
    </footer>
</body>
</html>
