<x-layouts.app :title="__('Post Details')">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-8">
            <a href="{{ route('posts.index') }}" class="text-indigo-600 hover:text-indigo-500 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 rounded">
                ← {{ __('Back to Posts') }}
            </a>
            <div class="mt-2 flex items-center justify-between gap-4">
                <h1 class="text-2xl font-bold text-gray-900 text-pretty">{{ __('Post Details') }}</h1>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $post->getStatusBadgeClass() }}">
                    {{ ucfirst($post->status) }}
                </span>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
            <div class="prose max-w-none">
                <p class="whitespace-pre-wrap break-words">{{ $post->content }}</p>
            </div>

            @if($post->hasMedia())
                <div class="mt-4">
                    <img
                        src="{{ $post->getPublicMediaUrl() }}"
                        alt="{{ __('Post image') }}"
                        width="800"
                        height="600"
                        class="max-h-96 w-auto rounded-lg"
                        loading="lazy"
                    >
                </div>
            @endif

            <div class="mt-6 pt-4 border-t border-gray-200 text-sm text-gray-500">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                    <span>{{ __('Created') }}: {{ $post->created_at->timezone(auth()->user()->timezone ?? config('app.timezone'))->translatedFormat('j M Y, g:i A') }}</span>
                    @if($post->scheduled_at)
                        <span>{{ __('Scheduled for') }}: {{ $post->scheduled_at->timezone($post->timezone)->translatedFormat('j M Y, g:i A') }} ({{ $post->timezone }})</span>
                    @endif
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4 text-pretty">{{ __('Channel Status') }}</h2>

            <div class="space-y-4">
                @forelse($post->postChannels as $postChannel)
                    <div class="flex items-center justify-between gap-4 p-4 rounded-lg border border-gray-200">
                        <div class="flex items-center min-w-0">
                            @if($postChannel->socialAccount->avatar_url)
                                <img src="{{ $postChannel->socialAccount->avatar_url }}" alt="" width="40" height="40" class="w-10 h-10 rounded-full shrink-0" loading="lazy">
                            @else
                                <div class="w-10 h-10 rounded-full shrink-0 {{ $postChannel->socialAccount->isFacebookPage() ? 'bg-blue-100' : 'bg-pink-100' }} flex items-center justify-center" aria-hidden="true">
                                    @if($postChannel->socialAccount->isFacebookPage())
                                        <svg class="w-5 h-5 text-blue-600" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                        </svg>
                                    @else
                                        <svg class="w-5 h-5 text-pink-600" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069z"/>
                                        </svg>
                                    @endif
                                </div>
                            @endif
                            <div class="ml-3 min-w-0">
                                <p class="font-medium text-gray-900 truncate">{{ $postChannel->socialAccount->platform_name }}</p>
                                <p class="text-sm text-gray-500">{{ $postChannel->socialAccount->getPlatformLabel() }}</p>
                            </div>
                        </div>

                        <div class="text-right shrink-0">
                            @if($postChannel->isPublished())
                                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-800">
                                    {{ __('Published') }}
                                </span>
                                @if($postChannel->permalink)
                                    <a href="{{ $postChannel->permalink }}" target="_blank" rel="noopener noreferrer" class="block text-sm text-indigo-600 hover:text-indigo-500 mt-1 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 rounded">
                                        {{ __('View post') }} →
                                    </a>
                                @endif
                            @elseif($postChannel->isFailed())
                                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-red-100 text-red-800">
                                    {{ __('Failed') }}
                                </span>
                                @if($postChannel->error_message)
                                    <p class="text-sm text-red-600 mt-1 max-w-xs break-words">{{ $postChannel->error_message }}</p>
                                @endif
                            @elseif($postChannel->isPublishing())
                                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-yellow-100 text-yellow-800">
                                    {{ __('Publishing…') }}
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-gray-100 text-gray-800">
                                    {{ __('Pending') }}
                                </span>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">{{ __('Please select at least one channel.') }}</p>
                @endforelse
            </div>
        </div>

        @php
            $publishedChannels = $post->postChannels->filter(
                fn ($channel) => $channel->isPublished() && filled($channel->platform_post_id)
            );
        @endphp

        @if($publishedChannels->isNotEmpty())
            <section class="bg-white rounded-xl border border-gray-200 p-6 mb-6" aria-labelledby="performance-heading" aria-live="polite">
                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-2">
                    <div>
                        <h2 id="performance-heading" class="text-lg font-semibold text-gray-900 text-pretty">{{ __('Performance') }}</h2>
                        <p class="text-sm text-gray-500 mt-1">{{ __('Metrics from Meta for published channels.') }}</p>
                    </div>
                    <form action="{{ route('posts.insights.refresh', $post) }}" method="POST">
                        @csrf
                        <button
                            type="submit"
                            class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm font-medium text-gray-700 rounded-lg hover:bg-gray-50 transition-colors duration-150 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"
                            aria-label="{{ __('Refresh metrics') }}"
                        >
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            {{ __('Refresh metrics') }}
                        </button>
                    </form>
                </div>

                <div class="space-y-6 mt-6">
                    @foreach($publishedChannels as $postChannel)
                        <div class="rounded-lg border border-gray-200 p-4">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-4">
                                <div class="min-w-0">
                                    <p class="font-medium text-gray-900 truncate">{{ $postChannel->socialAccount->platform_name }}</p>
                                    <p class="text-sm text-gray-500">{{ $postChannel->socialAccount->getPlatformLabel() }}</p>
                                </div>
                                @if($postChannel->permalink)
                                    <a href="{{ $postChannel->permalink }}" target="_blank" rel="noopener noreferrer" class="text-sm text-indigo-600 hover:text-indigo-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 rounded shrink-0">
                                        {{ __('View post') }} →
                                    </a>
                                @endif
                            </div>

                            @if($postChannel->insights_error)
                                <p class="text-sm text-amber-800 bg-amber-50 border border-amber-100 rounded-lg px-3 py-2 break-words">
                                    {{ $postChannel->insights_error }}
                                </p>
                            @elseif(! $postChannel->insights_fetched_at)
                                <p class="text-sm text-gray-500">{{ __('No metrics yet. Click Refresh metrics.') }}</p>
                            @else
                                <dl class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                                    <div>
                                        <dt class="text-xs uppercase tracking-wide text-gray-500">{{ __('Reach') }}</dt>
                                        <dd class="mt-1 text-2xl font-semibold text-gray-900 tabular-nums">
                                            {{ $postChannel->insights_reach !== null ? number_format($postChannel->insights_reach) : '—' }}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt class="text-xs uppercase tracking-wide text-gray-500">{{ __('Views') }}</dt>
                                        <dd class="mt-1 text-2xl font-semibold text-gray-900 tabular-nums">
                                            {{ $postChannel->insights_views !== null ? number_format($postChannel->insights_views) : '—' }}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt class="text-xs uppercase tracking-wide text-gray-500">{{ __('Likes') }}</dt>
                                        <dd class="mt-1 text-2xl font-semibold text-gray-900 tabular-nums">
                                            {{ $postChannel->insights_likes !== null ? number_format($postChannel->insights_likes) : '—' }}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt class="text-xs uppercase tracking-wide text-gray-500">{{ __('Comments') }}</dt>
                                        <dd class="mt-1 text-2xl font-semibold text-gray-900 tabular-nums">
                                            {{ $postChannel->insights_comments !== null ? number_format($postChannel->insights_comments) : '—' }}
                                        </dd>
                                    </div>
                                </dl>

                                <p class="mt-3 text-xs text-gray-500">
                                    {{ __('Updated :time', ['time' => $postChannel->insights_fetched_at->timezone(auth()->user()->timezone ?? config('app.timezone'))->translatedFormat('j M Y, g:i A')]) }}
                                </p>
                            @endif
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        <div class="flex items-center justify-between gap-4 flex-wrap">
            <div class="flex items-center flex-wrap gap-3">
                @if($post->canBeEdited())
                    <a href="{{ route('posts.edit', $post) }}" class="inline-flex items-center px-4 py-2 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition-colors duration-150 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        {{ __('Edit') }}
                    </a>
                @endif

                @if($post->canBeCancelled())
                    <form action="{{ route('posts.cancel', $post) }}" method="POST" class="inline" onsubmit="return confirm('{{ __('Are you sure you want to cancel this scheduled post?') }}')">
                        @csrf
                        <button type="submit" class="inline-flex items-center px-4 py-2 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition-colors duration-150 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                            {{ __('Cancel') }}
                        </button>
                    </form>
                @endif

                @if(in_array($post->status, ['draft', 'scheduled', 'failed']))
                    @if($post->postChannels->isNotEmpty())
                        <form
                            action="{{ route('posts.publish-now', $post) }}"
                            method="POST"
                            class="inline"
                            x-data="{ submitting: false }"
                            @submit="if (submitting) { $event.preventDefault(); return; } submitting = true"
                        >
                            @csrf
                            <button
                                type="submit"
                                class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 transition-colors duration-150 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 disabled:opacity-60 disabled:cursor-not-allowed"
                                :disabled="submitting"
                                :aria-busy="submitting"
                            >
                                <svg x-show="submitting" x-cloak class="animate-spin -ml-0.5 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <svg x-show="!submitting" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                </svg>
                                <span x-text="submitting ? @js(__('Publishing…')) : @js(__('Publish Now'))"></span>
                            </button>
                        </form>
                    @else
                        <p class="text-sm text-amber-700">
                            {{ __('Please select at least one channel.') }}
                            @if($post->canBeEdited())
                                <a href="{{ route('posts.edit', $post) }}" class="ml-1 font-medium text-indigo-600 hover:text-indigo-500">{{ __('Edit') }}</a>
                            @endif
                        </p>
                    @endif
                @elseif($post->status === 'publishing')
                    <p class="text-sm text-amber-800 bg-amber-50 border border-amber-100 rounded-lg px-3 py-2" role="status">
                        {{ __('This post is still publishing. Refresh in a moment or wait for the scheduler.') }}
                    </p>
                @endif
            </div>

            <form action="{{ route('posts.destroy', $post) }}" method="POST" class="inline" onsubmit="return confirm('{{ __('Are you sure you want to delete this post?') }}')">
                @csrf
                @method('DELETE')
                <button type="submit" class="inline-flex items-center px-4 py-2 text-red-600 font-medium hover:text-red-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-500 rounded">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    {{ __('Delete') }}
                </button>
            </form>
        </div>
    </div>
</x-layouts.app>
