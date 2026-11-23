<x-layouts.app :title="__('Dashboard')">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-gray-900">{{ __('Dashboard') }}</h1>
            <p class="text-gray-600">{{ __('Welcome back, :name!', ['name' => auth()->user()->name]) }}</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-lg bg-indigo-100">
                        <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500">{{ __('Connected Accounts') }}</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $stats['connected_accounts'] }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-lg bg-blue-100">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500">{{ __('Scheduled') }}</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $stats['scheduled_posts'] }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-lg bg-green-100">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500">{{ __('Published') }}</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $stats['published_posts'] }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-lg bg-red-100">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500">{{ __('Failed') }}</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $stats['failed_posts'] }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <div class="bg-white rounded-xl border border-gray-200">
                <div class="p-6 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-gray-900">{{ __('Upcoming Posts') }}</h2>
                        <a href="{{ route('posts.create') }}" class="text-sm text-indigo-600 hover:text-indigo-500">{{ __('Create New') }} →</a>
                    </div>
                </div>
                <div class="p-6">
                    @if($upcomingPosts->isEmpty())
                        <p class="text-gray-500 text-center py-4">{{ __('No scheduled posts yet.') }}</p>
                    @else
                        <div class="space-y-4">
                            @foreach($upcomingPosts as $post)
                                <a href="{{ route('posts.show', $post) }}" class="block p-4 rounded-lg border border-gray-100 hover:bg-gray-50 transition">
                                    <p class="text-gray-900 line-clamp-2 mb-2">{{ Str::limit($post->content, 100) }}</p>
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-gray-500">{{ $post->scheduled_at->diffForHumans() }}</span>
                                        <span class="text-gray-500">{{ $post->channels->count() }} {{ __('channel(s)') }}</span>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <div class="bg-white rounded-xl border border-gray-200">
                <div class="p-6 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-gray-900">{{ __('Recent Activity') }}</h2>
                        <a href="{{ route('posts.index') }}" class="text-sm text-indigo-600 hover:text-indigo-500">{{ __('View All') }} →</a>
                    </div>
                </div>
                <div class="p-6">
                    @if($recentPosts->isEmpty())
                        <p class="text-gray-500 text-center py-4">{{ __('No activity yet.') }}</p>
                    @else
                        <div class="space-y-4">
                            @foreach($recentPosts as $post)
                                <a href="{{ route('posts.show', $post) }}" class="block p-4 rounded-lg border border-gray-100 hover:bg-gray-50 transition">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium {{ $post->getStatusBadgeClass() }}">
                                            {{ ucfirst($post->status) }}
                                        </span>
                                        <span class="text-sm text-gray-500">{{ $post->published_at?->diffForHumans() ?? $post->created_at->diffForHumans() }}</span>
                                    </div>
                                    <p class="text-gray-900 line-clamp-2">{{ Str::limit($post->content, 100) }}</p>
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>

        @if($accounts->isEmpty())
            <div class="mt-8 bg-indigo-50 border border-indigo-200 rounded-xl p-6 text-center">
                <svg class="w-12 h-12 text-indigo-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                <h3 class="text-lg font-medium text-gray-900 mb-2">{{ __('Connect Your First Account') }}</h3>
                <p class="text-gray-600 mb-4">{{ __('Connect a Facebook Page or Instagram account to start scheduling posts.') }}</p>
                <a href="{{ route('accounts.connect') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 transition">
                    {{ __('Connect Account') }}
                </a>
            </div>
        @endif
    </div>
</x-layouts.app>
