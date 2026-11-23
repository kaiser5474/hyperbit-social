<x-layouts.app :title="__('Posts')">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ __('Posts') }}</h1>
                <p class="text-gray-600">{{ __('Manage your scheduled and published posts.') }}</p>
            </div>
            <a href="{{ route('posts.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 transition">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                {{ __('Create Post') }}
            </a>
        </div>

        <div class="mb-6 flex items-center space-x-4">
            <a href="{{ route('posts.index') }}" class="px-4 py-2 rounded-lg text-sm font-medium {{ !request('status') ? 'bg-indigo-100 text-indigo-700' : 'text-gray-500 hover:text-gray-700' }}">
                {{ __('All') }}
            </a>
            <a href="{{ route('posts.index', ['status' => 'scheduled']) }}" class="px-4 py-2 rounded-lg text-sm font-medium {{ request('status') === 'scheduled' ? 'bg-indigo-100 text-indigo-700' : 'text-gray-500 hover:text-gray-700' }}">
                {{ __('Scheduled') }}
            </a>
            <a href="{{ route('posts.index', ['status' => 'published']) }}" class="px-4 py-2 rounded-lg text-sm font-medium {{ request('status') === 'published' ? 'bg-indigo-100 text-indigo-700' : 'text-gray-500 hover:text-gray-700' }}">
                {{ __('Published') }}
            </a>
            <a href="{{ route('posts.index', ['status' => 'failed']) }}" class="px-4 py-2 rounded-lg text-sm font-medium {{ request('status') === 'failed' ? 'bg-indigo-100 text-indigo-700' : 'text-gray-500 hover:text-gray-700' }}">
                {{ __('Failed') }}
            </a>
        </div>

        @if($posts->isEmpty())
            <div class="bg-white rounded-xl border border-gray-200 p-12 text-center">
                <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <h3 class="text-lg font-medium text-gray-900 mb-2">{{ __('No posts yet') }}</h3>
                <p class="text-gray-500 mb-6">{{ __('Create your first post to get started.') }}</p>
                <a href="{{ route('posts.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 transition">
                    {{ __('Create Your First Post') }}
                </a>
            </div>
        @else
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="divide-y divide-gray-200">
                    @foreach($posts as $post)
                        <a href="{{ route('posts.show', $post) }}" class="block p-6 hover:bg-gray-50 transition">
                            <div class="flex items-start justify-between">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center space-x-3 mb-2">
                                        <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium {{ $post->getStatusBadgeClass() }}">
                                            {{ ucfirst($post->status) }}
                                        </span>
                                        <span class="text-sm text-gray-500">
                                            @if($post->scheduled_at)
                                                {{ $post->scheduled_at->format('M j, Y g:i A') }}
                                            @else
                                                {{ $post->created_at->format('M j, Y g:i A') }}
                                            @endif
                                        </span>
                                    </div>
                                    <p class="text-gray-900 line-clamp-2">{{ Str::limit($post->content, 200) }}</p>
                                    <div class="mt-2 flex items-center space-x-4 text-sm text-gray-500">
                                        <span>{{ $post->channels->count() }} {{ __('channel(s)') }}</span>
                                        @if($post->hasMedia())
                                            <span class="flex items-center">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                </svg>
                                                {{ __('Has image') }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                                @if($post->hasMedia())
                                    <div class="ml-4 flex-shrink-0">
                                        <img src="{{ $post->getPublicMediaUrl() }}" alt="" class="w-16 h-16 rounded-lg object-cover">
                                    </div>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>

            <div class="mt-6">
                {{ $posts->links() }}
            </div>
        @endif
    </div>
</x-layouts.app>
