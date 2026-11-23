<x-layouts.landing :title="__('Data Deletion Status') . ' - HyperBit Social'">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <h1 class="text-3xl font-bold text-gray-900 mb-8">{{ __('Data Deletion Status') }}</h1>

        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-4">
                <span class="text-sm text-gray-500">{{ __('Confirmation Code') }}</span>
                <code class="bg-gray-100 px-3 py-1 rounded text-sm">{{ $deletionRequest->confirmation_code }}</code>
            </div>

            <div class="flex items-center justify-between mb-4">
                <span class="text-sm text-gray-500">{{ __('Request Date') }}</span>
                <span class="text-gray-900">{{ $deletionRequest->requested_at->format('F j, Y g:i A') }}</span>
            </div>

            <div class="flex items-center justify-between mb-6">
                <span class="text-sm text-gray-500">{{ __('Status') }}</span>
                @if($deletionRequest->status === 'completed')
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                        {{ __('Completed') }}
                    </span>
                @elseif($deletionRequest->status === 'processing')
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800">
                        <svg class="w-4 h-4 mr-1 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        {{ __('Processing') }}
                    </span>
                @else
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800">
                        {{ __('Pending') }}
                    </span>
                @endif
            </div>

            @if($deletionRequest->status === 'completed' && $deletionRequest->completed_at)
                <div class="border-t border-gray-200 pt-4">
                    <p class="text-sm text-gray-600">
                        {{ __('Your data was successfully deleted on') }} {{ $deletionRequest->completed_at->format('F j, Y g:i A') }}.
                    </p>
                </div>
            @endif
        </div>

        <div class="mt-8 text-center">
            <a href="{{ route('home') }}" class="text-indigo-600 hover:text-indigo-500">
                ← {{ __('Back to Home') }}
            </a>
        </div>
    </div>
</x-layouts.landing>
