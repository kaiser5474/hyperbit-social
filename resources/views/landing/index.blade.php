@php
$jsonLd = [
    '@context' => 'https://schema.org',
    '@graph' => [
        [
            '@type' => 'SoftwareApplication',
            'name' => 'HyperBit Social',
            'applicationCategory' => 'BusinessApplication',
            'operatingSystem' => 'Web',
            'offers' => [
                '@type' => 'Offer',
                'price' => '0',
                'priceCurrency' => 'USD',
            ],
            'description' => __('Open-source social media scheduler for Facebook and Instagram. Self-hostable Buffer alternative.'),
            'url' => url('/'),
            'author' => [
                '@type' => 'Organization',
                'name' => 'HyperBit IT',
                'url' => 'https://hyperbitit.com',
            ],
        ],
        [
            '@type' => 'Organization',
            'name' => 'HyperBit IT',
            'url' => 'https://hyperbitit.com',
            'logo' => asset('images/logo.png'),
        ],
    ],
];
@endphp

<x-layouts.landing :json-ld="$jsonLd">
    <section class="relative overflow-hidden bg-gradient-to-br from-indigo-50 via-white to-purple-50">
        <div class="absolute inset-0 bg-grid-pattern opacity-5"></div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24 md:py-32">
            <div class="text-center">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-indigo-100 text-indigo-800 mb-6">
                    🚀 {{ __('Open Source') }} · MIT License
                </span>
                <h1 class="text-4xl md:text-6xl font-extrabold text-gray-900 tracking-tight mb-6">
                    {{ __('Schedule Your Social Media') }}
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-600 to-purple-600">{{ __('Like a Pro') }}</span>
                </h1>
                <p class="text-xl text-gray-600 max-w-2xl mx-auto mb-8">
                    {{ __('Publish to Facebook Pages and Instagram from one place. Self-hostable, open-source alternative to Buffer.') }}
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    @auth
                        <a href="{{ route('dashboard') }}" class="inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-xl text-white bg-indigo-600 hover:bg-indigo-700 shadow-lg shadow-indigo-500/30 transition">
                            {{ __('Go to Dashboard') }}
                        </a>
                    @else
                        <a href="{{ route('auth.facebook') }}" class="inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-xl text-white bg-indigo-600 hover:bg-indigo-700 shadow-lg shadow-indigo-500/30 transition">
                            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                            </svg>
                            {{ __('Start Free with Facebook') }}
                        </a>
                    @endauth
                    <a href="https://github.com/kaiser5474/hyperbit-social" target="_blank" class="inline-flex items-center justify-center px-6 py-3 border border-gray-300 text-base font-medium rounded-xl text-gray-700 bg-white hover:bg-gray-50 transition">
                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 24 24">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z"/>
                        </svg>
                        {{ __('View on GitHub') }}
                    </a>
                </div>

                <div class="mt-6 p-4 bg-amber-50 border border-amber-200 rounded-xl max-w-xl mx-auto">
                    <p class="text-sm text-amber-800">
                        <strong>{{ __('Note:') }}</strong> {{ __('Until Meta App Review is complete, only app testers can log in. Contact us to be added as a tester.') }}
                    </p>
                </div>
            </div>
        </div>
    </section>

    <section id="features" class="py-24 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">{{ __('Everything You Need') }}</h2>
                <p class="text-xl text-gray-600 max-w-2xl mx-auto">{{ __('Powerful features to manage your social media presence efficiently.') }}</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <div class="p-6 rounded-2xl bg-gradient-to-br from-blue-50 to-indigo-50 border border-blue-100">
                    <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ __('Schedule Posts') }}</h3>
                    <p class="text-gray-600">{{ __('Plan and schedule your content in advance. Set the perfect time for your audience.') }}</p>
                </div>

                <div class="p-6 rounded-2xl bg-gradient-to-br from-purple-50 to-pink-50 border border-purple-100">
                    <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ __('Multiple Accounts') }}</h3>
                    <p class="text-gray-600">{{ __('Connect multiple Facebook Pages and Instagram accounts. Manage all from one dashboard.') }}</p>
                </div>

                <div class="p-6 rounded-2xl bg-gradient-to-br from-green-50 to-emerald-50 border border-green-100">
                    <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ __('Image Support') }}</h3>
                    <p class="text-gray-600">{{ __('Upload and schedule image posts. Visual content performs better on social media.') }}</p>
                </div>

                <div class="p-6 rounded-2xl bg-gradient-to-br from-orange-50 to-amber-50 border border-orange-100">
                    <div class="w-12 h-12 bg-orange-100 rounded-xl flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ __('Instant Publish') }}</h3>
                    <p class="text-gray-600">{{ __('Publish immediately when inspiration strikes. No need to wait.') }}</p>
                </div>

                <div class="p-6 rounded-2xl bg-gradient-to-br from-cyan-50 to-teal-50 border border-cyan-100">
                    <div class="w-12 h-12 bg-cyan-100 rounded-xl flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ __('Self-Hosted') }}</h3>
                    <p class="text-gray-600">{{ __('Host on your own server. Keep full control of your data and credentials.') }}</p>
                </div>

                <div class="p-6 rounded-2xl bg-gradient-to-br from-rose-50 to-red-50 border border-rose-100">
                    <div class="w-12 h-12 bg-rose-100 rounded-xl flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ __('Open Source') }}</h3>
                    <p class="text-gray-600">{{ __('MIT licensed. Customize, extend, or contribute to the project.') }}</p>
                </div>
            </div>
        </div>
    </section>

    <section id="how-it-works" class="py-24 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">{{ __('How It Works') }}</h2>
                <p class="text-xl text-gray-600 max-w-2xl mx-auto">{{ __('Get started in minutes with these simple steps.') }}</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="text-center">
                    <div class="w-16 h-16 bg-indigo-600 text-white rounded-full flex items-center justify-center text-2xl font-bold mx-auto mb-4">1</div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ __('Connect Your Accounts') }}</h3>
                    <p class="text-gray-600">{{ __('Log in with Facebook and connect your Pages and Instagram accounts.') }}</p>
                </div>

                <div class="text-center">
                    <div class="w-16 h-16 bg-indigo-600 text-white rounded-full flex items-center justify-center text-2xl font-bold mx-auto mb-4">2</div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ __('Create Your Post') }}</h3>
                    <p class="text-gray-600">{{ __('Write your content, add an image, and select which accounts to post to.') }}</p>
                </div>

                <div class="text-center">
                    <div class="w-16 h-16 bg-indigo-600 text-white rounded-full flex items-center justify-center text-2xl font-bold mx-auto mb-4">3</div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ __('Schedule or Publish') }}</h3>
                    <p class="text-gray-600">{{ __('Publish immediately or schedule for the perfect time. We handle the rest.') }}</p>
                </div>
            </div>
        </div>
    </section>

    <section id="open-source" class="py-24 bg-gray-900 text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="lg:flex lg:items-center lg:justify-between">
                <div class="lg:w-1/2 mb-8 lg:mb-0">
                    <h2 class="text-3xl md:text-4xl font-bold mb-4">{{ __('Open Source on GitHub') }}</h2>
                    <p class="text-xl text-gray-300 mb-6">{{ __('Built with Laravel, the best PHP framework. Contribute, customize, or deploy your own instance.') }}</p>
                    <ul class="space-y-3 text-gray-300">
                        <li class="flex items-center">
                            <svg class="w-5 h-5 text-green-400 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            {{ __('MIT License - Use it for anything') }}
                        </li>
                        <li class="flex items-center">
                            <svg class="w-5 h-5 text-green-400 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            {{ __('Works on shared hosting') }}
                        </li>
                        <li class="flex items-center">
                            <svg class="w-5 h-5 text-green-400 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            {{ __('Well documented') }}
                        </li>
                    </ul>
                    <a href="https://github.com/kaiser5474/hyperbit-social" target="_blank" class="inline-flex items-center mt-6 px-6 py-3 border border-white text-base font-medium rounded-xl text-white hover:bg-white hover:text-gray-900 transition">
                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 24 24">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z"/>
                        </svg>
                        {{ __('Star on GitHub') }}
                    </a>
                </div>
                <div class="lg:w-1/2 lg:pl-12">
                    <div class="bg-gray-800 rounded-xl p-6 font-mono text-sm">
                        <div class="flex items-center space-x-2 mb-4">
                            <div class="w-3 h-3 rounded-full bg-red-500"></div>
                            <div class="w-3 h-3 rounded-full bg-yellow-500"></div>
                            <div class="w-3 h-3 rounded-full bg-green-500"></div>
                        </div>
                        <code class="text-green-400">$</code>
                        <code class="text-gray-300"> git clone https://github.com/kaiser5474/hyperbit-social.git</code>
                        <br><br>
                        <code class="text-green-400">$</code>
                        <code class="text-gray-300"> cd hyperbit-social && composer install</code>
                        <br><br>
                        <code class="text-green-400">$</code>
                        <code class="text-gray-300"> cp .env.example .env && php artisan key:generate</code>
                        <br><br>
                        <code class="text-green-400">$</code>
                        <code class="text-gray-300"> php artisan migrate && php artisan serve</code>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="pricing" class="py-24 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">{{ __('Simple Pricing') }}</h2>
                <p class="text-xl text-gray-600 max-w-2xl mx-auto">{{ __('Self-host for free, or let us host it for you.') }}</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 max-w-4xl mx-auto">
                <div class="border-2 border-gray-200 rounded-2xl p-8">
                    <h3 class="text-2xl font-bold text-gray-900 mb-2">{{ __('Self-Hosted') }}</h3>
                    <div class="text-4xl font-bold text-gray-900 mb-4">{{ __('Free') }}</div>
                    <p class="text-gray-600 mb-6">{{ __('Deploy on your own server') }}</p>
                    <ul class="space-y-3 mb-8">
                        <li class="flex items-center text-gray-600">
                            <svg class="w-5 h-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            {{ __('Unlimited accounts') }}
                        </li>
                        <li class="flex items-center text-gray-600">
                            <svg class="w-5 h-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            {{ __('Unlimited scheduled posts') }}
                        </li>
                        <li class="flex items-center text-gray-600">
                            <svg class="w-5 h-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            {{ __('Full source code') }}
                        </li>
                        <li class="flex items-center text-gray-600">
                            <svg class="w-5 h-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            {{ __('Community support') }}
                        </li>
                    </ul>
                    <a href="https://github.com/kaiser5474/hyperbit-social" target="_blank" class="block w-full text-center px-6 py-3 border-2 border-indigo-600 text-indigo-600 font-medium rounded-xl hover:bg-indigo-50 transition">
                        {{ __('Download from GitHub') }}
                    </a>
                </div>

                <div class="border-2 border-indigo-600 rounded-2xl p-8 relative">
                    <div class="absolute -top-3 left-1/2 transform -translate-x-1/2">
                        <span class="bg-indigo-600 text-white text-sm font-medium px-3 py-1 rounded-full">{{ __('Coming Soon') }}</span>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-2">{{ __('Hosted') }}</h3>
                    <div class="text-3xl font-bold text-indigo-600 mb-4">{{ __('Coming Soon') }}</div>
                    <p class="text-gray-600 mb-6">{{ __("We'll host it for you") }}</p>
                    <ul class="space-y-3 mb-8">
                        <li class="flex items-center text-gray-600">
                            <svg class="w-5 h-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            {{ __('Everything in Self-Hosted') }}
                        </li>
                        <li class="flex items-center text-gray-600">
                            <svg class="w-5 h-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            {{ __('Managed hosting') }}
                        </li>
                        <li class="flex items-center text-gray-600">
                            <svg class="w-5 h-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            {{ __('Automatic updates') }}
                        </li>
                        <li class="flex items-center text-gray-600">
                            <svg class="w-5 h-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            {{ __('Priority support') }}
                        </li>
                    </ul>
                    <a href="https://hyperbitit.com" target="_blank" class="block w-full text-center px-6 py-3 bg-indigo-600 text-white font-medium rounded-xl hover:bg-indigo-700 transition">
                        {{ __('Contact Us') }}
                    </a>
                </div>
            </div>
        </div>
    </section>

    <section id="faq" class="py-24 bg-gray-50">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">{{ __('Frequently Asked Questions') }}</h2>
            </div>

            <div class="space-y-4" x-data="{ open: null }">
                <div class="bg-white rounded-xl border border-gray-200">
                    <button @click="open = open === 1 ? null : 1" class="flex justify-between items-center w-full px-6 py-4 text-left">
                        <span class="font-medium text-gray-900">{{ __('What platforms are supported?') }}</span>
                        <svg class="w-5 h-5 text-gray-500 transform transition-transform" :class="{ 'rotate-180': open === 1 }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div x-show="open === 1" x-collapse class="px-6 pb-4 text-gray-600">
                        {{ __('Currently, HyperBit Social supports Facebook Pages and Instagram Business accounts. Threads support is coming soon.') }}
                    </div>
                </div>

                <div class="bg-white rounded-xl border border-gray-200">
                    <button @click="open = open === 2 ? null : 2" class="flex justify-between items-center w-full px-6 py-4 text-left">
                        <span class="font-medium text-gray-900">{{ __('Do I need my own Meta App?') }}</span>
                        <svg class="w-5 h-5 text-gray-500 transform transition-transform" :class="{ 'rotate-180': open === 2 }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div x-show="open === 2" x-collapse class="px-6 pb-4 text-gray-600">
                        {{ __("For self-hosting, yes. You'll need to create a Meta App and configure it with the proper permissions. We provide detailed setup instructions.") }}
                    </div>
                </div>

                <div class="bg-white rounded-xl border border-gray-200">
                    <button @click="open = open === 3 ? null : 3" class="flex justify-between items-center w-full px-6 py-4 text-left">
                        <span class="font-medium text-gray-900">{{ __('Can I use this on shared hosting?') }}</span>
                        <svg class="w-5 h-5 text-gray-500 transform transition-transform" :class="{ 'rotate-180': open === 3 }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div x-show="open === 3" x-collapse class="px-6 pb-4 text-gray-600">
                        {{ __("Yes! HyperBit Social is specifically designed to work on shared hosting like Hostinger. It uses a cron job for scheduling instead of workers.") }}
                    </div>
                </div>

                <div class="bg-white rounded-xl border border-gray-200">
                    <button @click="open = open === 4 ? null : 4" class="flex justify-between items-center w-full px-6 py-4 text-left">
                        <span class="font-medium text-gray-900">{{ __('How is my data protected?') }}</span>
                        <svg class="w-5 h-5 text-gray-500 transform transition-transform" :class="{ 'rotate-180': open === 4 }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div x-show="open === 4" x-collapse class="px-6 pb-4 text-gray-600">
                        {{ __('Access tokens are encrypted at rest using Laravel\'s encryption. For self-hosted instances, you have full control over your data.') }}
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-16 bg-gradient-to-r from-indigo-600 to-purple-600 text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-3xl font-bold mb-4">{{ __('Need a Custom Solution?') }}</h2>
            <p class="text-xl text-indigo-100 mb-8 max-w-2xl mx-auto">
                {{ __('Looking for custom social media automation, chatbots, or integrations? HyperBit IT can build it for you.') }}
            </p>
            <a href="https://hyperbitit.com" target="_blank" class="inline-flex items-center px-6 py-3 border-2 border-white text-base font-medium rounded-xl text-white hover:bg-white hover:text-indigo-600 transition">
                {{ __('Visit HyperBit IT') }}
                <svg class="ml-2 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                </svg>
            </a>
        </div>
    </section>
</x-layouts.landing>
