<x-layouts.app :title="__('Settings')">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-gray-900">{{ __('Settings') }}</h1>
            <p class="text-gray-600">{{ __('Manage your account preferences.') }}</p>
        </div>

        <form action="{{ route('settings.update') }}" method="POST">
            @csrf
            @method('PUT')

            <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">{{ __('Profile') }}</h2>

                <div class="flex items-center mb-6">
                    @if($user->avatar)
                        <img src="{{ $user->avatar }}" alt="{{ $user->name }}" class="w-16 h-16 rounded-full">
                    @else
                        <div class="w-16 h-16 rounded-full bg-indigo-100 flex items-center justify-center">
                            <span class="text-2xl font-semibold text-indigo-600">{{ substr($user->name, 0, 1) }}</span>
                        </div>
                    @endif
                    <div class="ml-4">
                        <p class="font-medium text-gray-900">{{ $user->name }}</p>
                        <p class="text-sm text-gray-500">{{ $user->email }}</p>
                    </div>
                </div>

                <p class="text-sm text-gray-500">
                    {{ __('Your profile information is synced from Facebook.') }}
                </p>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">{{ __('Preferences') }}</h2>

                <div class="space-y-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('Timezone') }}</label>
                        <select name="timezone" class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                            @foreach($timezones as $tz => $label)
                                <option value="{{ $tz }}" {{ old('timezone', $user->timezone) === $tz ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-sm text-gray-500">{{ __('Used for scheduling posts.') }}</p>
                        @error('timezone')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('Language') }}</label>
                        <select name="locale" class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                            @foreach($locales as $code => $label)
                                <option value="{{ $code }}" {{ old('locale', $user->locale) === $code ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('locale')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end">
                <button type="submit" class="px-6 py-2 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 transition">
                    {{ __('Save Changes') }}
                </button>
            </div>
        </form>
    </div>
</x-layouts.app>
