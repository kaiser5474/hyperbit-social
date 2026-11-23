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

        <div
            x-data="postIndex"
            @click="handleClick($event)"
            :class="{ 'opacity-60 pointer-events-none': loading }"
            :aria-busy="loading"
        >
            <div x-ref="panel">
                @include('posts.partials.panel')
            </div>
        </div>
    </div>
</x-layouts.app>
