<x-layouts.app :title="__('Connect Accounts')">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-8">
            <a href="{{ route('accounts.index') }}" class="text-indigo-600 hover:text-indigo-500 text-sm">
                ← {{ __('Back to Accounts') }}
            </a>
            <h1 class="text-2xl font-bold text-gray-900 mt-2">{{ __('Connect Accounts') }}</h1>
            <p class="text-gray-600">{{ __('Select the Facebook Pages and Instagram accounts you want to connect.') }}</p>
        </div>

        @if(empty($availablePages))
            <div class="bg-white rounded-xl border border-gray-200 p-12 text-center">
                <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <h3 class="text-lg font-medium text-gray-900 mb-2">{{ __('No Pages Found') }}</h3>
                <p class="text-gray-500 mb-6">{{ __('You don\'t have any Facebook Pages to connect. Create a Facebook Page first, then come back here.') }}</p>
                <a href="https://www.facebook.com/pages/create" target="_blank" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition">
                    {{ __('Create a Facebook Page') }}
                </a>
            </div>
        @else
            <form action="{{ route('accounts.store') }}" method="POST" x-data="{ selectedAccounts: [] }">
                @csrf

                <div class="space-y-4">
                    @foreach($availablePages as $item)
                        @php
                            $page = $item['page'];
                            $instagram = $item['instagram'];
                            $pageConnected = $item['is_connected'];
                        @endphp

                        <div class="bg-white rounded-xl border border-gray-200 p-6">
                            <div class="flex items-start">
                                <label class="flex items-center cursor-pointer">
                                    <input type="checkbox"
                                           name="accounts[]"
                                           value="{{ json_encode([
                                               'type' => 'facebook_page',
                                               'id' => $page['id'],
                                               'name' => $page['name'],
                                               'token' => $page['access_token'],
                                               'avatar' => $page['picture']['data']['url'] ?? null,
                                           ]) }}"
                                           class="w-5 h-5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                           {{ $pageConnected ? 'checked' : '' }}
                                           x-model="selectedAccounts">
                                </label>
                                <div class="ml-4 flex-1">
                                    <div class="flex items-center">
                                        @if(isset($page['picture']['data']['url']))
                                            <img src="{{ $page['picture']['data']['url'] }}" alt="{{ $page['name'] }}" class="w-10 h-10 rounded-full">
                                        @else
                                            <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
                                                <svg class="w-5 h-5 text-blue-600" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                                </svg>
                                            </div>
                                        @endif
                                        <div class="ml-3">
                                            <p class="font-medium text-gray-900">{{ $page['name'] }}</p>
                                            <p class="text-sm text-gray-500">{{ __('Facebook Page') }} · {{ $page['category'] ?? '' }}</p>
                                        </div>
                                        @if($pageConnected)
                                            <span class="ml-auto text-xs bg-green-100 text-green-800 px-2 py-1 rounded">{{ __('Connected') }}</span>
                                        @endif
                                    </div>

                                    @if($instagram)
                                        <div class="mt-4 ml-6 pl-4 border-l-2 border-gray-200">
                                            <label class="flex items-center cursor-pointer">
                                                <input type="checkbox"
                                                       name="accounts[]"
                                                       value="{{ json_encode([
                                                           'type' => 'instagram_business',
                                                           'id' => $instagram['id'],
                                                           'name' => $instagram['name'] ?? $instagram['username'],
                                                           'username' => $instagram['username'],
                                                           'token' => $page['access_token'],
                                                           'avatar' => $instagram['profile_picture_url'] ?? null,
                                                           'parent_page_id' => $page['id'],
                                                       ]) }}"
                                                       class="w-5 h-5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                                       {{ $instagram['is_connected'] ?? false ? 'checked' : '' }}
                                                       x-model="selectedAccounts">
                                                <div class="ml-3 flex items-center">
                                                    @if(isset($instagram['profile_picture_url']))
                                                        <img src="{{ $instagram['profile_picture_url'] }}" alt="{{ $instagram['username'] }}" class="w-8 h-8 rounded-full">
                                                    @else
                                                        <div class="w-8 h-8 rounded-full bg-pink-100 flex items-center justify-center">
                                                            <svg class="w-4 h-4 text-pink-600" fill="currentColor" viewBox="0 0 24 24">
                                                                <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073z"/>
                                                            </svg>
                                                        </div>
                                                    @endif
                                                    <div class="ml-2">
                                                        <p class="text-sm font-medium text-gray-900">@{{ $instagram['username'] }}</p>
                                                        <p class="text-xs text-gray-500">{{ __('Instagram Business') }}</p>
                                                    </div>
                                                    @if($instagram['is_connected'] ?? false)
                                                        <span class="ml-2 text-xs bg-green-100 text-green-800 px-2 py-1 rounded">{{ __('Connected') }}</span>
                                                    @endif
                                                </div>
                                            </label>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-8 flex items-center justify-end space-x-4">
                    <a href="{{ route('accounts.index') }}" class="px-4 py-2 text-gray-700 font-medium hover:text-gray-900">
                        {{ __('Cancel') }}
                    </a>
                    <button type="submit" class="px-6 py-2 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 transition" x-bind:disabled="selectedAccounts.length === 0">
                        {{ __('Connect Selected') }}
                    </button>
                </div>
            </form>
        @endif
    </div>
</x-layouts.app>
