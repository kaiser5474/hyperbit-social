<x-layouts.app :title="__('Post Details')">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-8">
            <a href="{{ route('posts.index') }}" class="text-indigo-600 hover:text-indigo-500 text-sm">
                ← {{ __('Back to Posts') }}
            </a>
            <div class="mt-2 flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-900">{{ __('Post Details') }}</h1>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $post->getStatusBadgeClass() }}">
                    {{ ucfirst($post->status) }}
                </span>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
            <div class="prose max-w-none">
                <p class="whitespace-pre-wrap">{{ $post->content }}</p>
            </div>

            @if($post->hasMedia())
                <div class="mt-4">
                    <img src="{{ $post->getPublicMediaUrl() }}" alt="" class="max-h-96 rounded-lg">
                </div>
            @endif

            <div class="mt-6 pt-4 border-t border-gray-200 text-sm text-gray-500">
                <div class="flex items-center justify-between">
                    <span>{{ __('Created') }}: {{ $post->created_at->format('M j, Y g:i A') }}</span>
                    @if($post->scheduled_at)
                        <span>{{ __('Scheduled for') }}: {{ $post->scheduled_at->format('M j, Y g:i A') }} ({{ $post->timezone }})</span>
                    @endif
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">{{ __('Channel Status') }}</h2>

            <div class="space-y-4">
                @foreach($post->postChannels as $postChannel)
                    <div class="flex items-center justify-between p-4 rounded-lg border border-gray-200">
                        <div class="flex items-center">
                            @if($postChannel->socialAccount->avatar_url)
                                <img src="{{ $postChannel->socialAccount->avatar_url }}" alt="{{ $postChannel->socialAccount->platform_name }}" class="w-10 h-10 rounded-full">
                            @else
                                <div class="w-10 h-10 rounded-full {{ $postChannel->socialAccount->isFacebookPage() ? 'bg-blue-100' : 'bg-pink-100' }} flex items-center justify-center">
                                    @if($postChannel->socialAccount->isFacebookPage())
                                        <svg class="w-5 h-5 text-blue-600" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                        </svg>
                                    @else
                                        <svg class="w-5 h-5 text-pink-600" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069z"/>
                                        </svg>
                                    @endif
                                </div>
                            @endif
                            <div class="ml-3">
                                <p class="font-medium text-gray-900">{{ $postChannel->socialAccount->platform_name }}</p>
                                <p class="text-sm text-gray-500">{{ $postChannel->socialAccount->getPlatformLabel() }}</p>
                            </div>
                        </div>

                        <div class="text-right">
                            @if($postChannel->isPublished())
                                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-800">
                                    {{ __('Published') }}
                                </span>
                                @if($postChannel->permalink)
                                    <a href="{{ $postChannel->permalink }}" target="_blank" class="block text-sm text-indigo-600 hover:text-indigo-500 mt-1">
                                        {{ __('View post') }} →
                                    </a>
                                @endif
                            @elseif($postChannel->isFailed())
                                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-red-100 text-red-800">
                                    {{ __('Failed') }}
                                </span>
                                @if($postChannel->error_message)
                                    <p class="text-sm text-red-600 mt-1">{{ $postChannel->error_message }}</p>
                                @endif
                            @elseif($postChannel->isPublishing())
                                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-yellow-100 text-yellow-800">
                                    {{ __('Publishing...') }}
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-gray-100 text-gray-800">
                                    {{ __('Pending') }}
                                </span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                @if($post->canBeEdited())
                    <a href="{{ route('posts.edit', $post) }}" class="inline-flex items-center px-4 py-2 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        {{ __('Edit') }}
                    </a>
                @endif

                @if($post->canBeCancelled())
                    <form action="{{ route('posts.cancel', $post) }}" method="POST" class="inline" onsubmit="return confirm('{{ __('Are you sure you want to cancel this scheduled post?') }}')">
                        @csrf
                        <button type="submit" class="inline-flex items-center px-4 py-2 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                            {{ __('Cancel') }}
                        </button>
                    </form>
                @endif

                @if(in_array($post->status, ['draft', 'scheduled', 'failed']))
                    <form action="{{ route('posts.publish-now', $post) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 transition">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                            </svg>
                            {{ __('Publish Now') }}
                        </button>
                    </form>
                @endif
            </div>

            <form action="{{ route('posts.destroy', $post) }}" method="POST" class="inline" onsubmit="return confirm('{{ __('Are you sure you want to delete this post?') }}')">
                @csrf
                @method('DELETE')
                <button type="submit" class="inline-flex items-center px-4 py-2 text-red-600 font-medium hover:text-red-700">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    {{ __('Delete') }}
                </button>
            </form>
        </div>
    </div>
</x-layouts.app>
