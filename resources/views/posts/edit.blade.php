<x-layouts.app :title="__('Edit Post')">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-8">
            <a href="{{ route('posts.show', $post) }}" class="text-indigo-600 hover:text-indigo-500 text-sm">
                ← {{ __('Back to Post') }}
            </a>
            <h1 class="text-2xl font-bold text-gray-900 mt-2">{{ __('Edit Post') }}</h1>
        </div>

        <form action="{{ route('posts.update', $post) }}" method="POST" enctype="multipart/form-data" x-data="postComposer()" @submit="validateChannels($event)">
            @csrf
            @method('PUT')

            <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('Content') }}</label>
                <textarea
                    name="content"
                    rows="6"
                    x-model="content"
                    class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                    required
                >{{ old('content', $post->content) }}</textarea>
                <div class="mt-2 text-sm text-gray-500">
                    <span x-text="content.length"></span>/63,206
                </div>
                @error('content')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('Image') }}</label>

                @if($post->hasMedia())
                    <div class="mb-4" x-show="!removeMedia">
                        <img src="{{ $post->getPublicMediaUrl() }}" alt="" class="max-h-64 rounded-lg">
                        <label class="flex items-center mt-2">
                            <input type="checkbox" name="remove_media" value="1" class="w-4 h-4 rounded border-gray-300 text-red-600 focus:ring-red-500" x-model="removeMedia">
                            <span class="ml-2 text-sm text-red-600">{{ __('Remove current image') }}</span>
                        </label>
                    </div>
                @endif

                <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-lg">
                    <div class="space-y-1 text-center">
                        <div class="flex text-sm text-gray-600">
                            <label class="relative cursor-pointer bg-white rounded-md font-medium text-indigo-600 hover:text-indigo-500">
                                <span>{{ $post->hasMedia() ? __('Replace image') : __('Upload an image') }}</span>
                                <input type="file" name="media" class="sr-only" accept="image/jpeg,image/png,image/gif">
                            </label>
                        </div>
                        <p class="text-xs text-gray-500">{{ __('PNG, JPG, GIF up to 10MB') }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl border p-6 mb-6" :class="channelError ? 'border-red-400' : 'border-gray-200'" x-ref="channelsSection">
                <label class="block text-sm font-medium text-gray-700 mb-4">
                    {{ __('Select Channels') }}
                    <span class="text-red-500">*</span>
                </label>
                <div class="space-y-3">
                    @foreach($accounts as $account)
                        <label class="flex items-center p-3 rounded-lg border border-gray-200 hover:bg-gray-50 cursor-pointer">
                            <input type="checkbox" name="channels[]" value="{{ $account->id }}" x-model.number="selectedChannels" @change="channelError = selectedChannels.length === 0" class="w-5 h-5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            <div class="ml-3 flex items-center">
                                <div class="w-8 h-8 rounded-full {{ $account->isFacebookPage() ? 'bg-blue-100' : 'bg-pink-100' }} flex items-center justify-center">
                                    @if($account->isFacebookPage())
                                        <svg class="w-4 h-4 text-blue-600" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                        </svg>
                                    @else
                                        <svg class="w-4 h-4 text-pink-600" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069z"/>
                                        </svg>
                                    @endif
                                </div>
                                <div class="ml-2">
                                    <p class="text-sm font-medium text-gray-900">{{ $account->platform_name }}</p>
                                    <p class="text-xs text-gray-500">{{ $account->getPlatformLabel() }}</p>
                                </div>
                            </div>
                        </label>
                    @endforeach
                </div>
                <p x-show="channelError" x-cloak class="mt-3 text-sm text-red-600" role="alert">
                    {{ __('Please select at least one channel.') }}
                </p>
                @error('channels')
                    <p class="mt-3 text-sm text-red-600" x-show="!channelError">{{ $message }}</p>
                @enderror
            </div>

            @if($post->isScheduled())
                <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-4">{{ __('Schedule') }}</label>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">{{ __('Date and Time') }}</label>
                            <input type="datetime-local" name="scheduled_at" value="{{ old('scheduled_at', $post->getScheduledAtInUserTimezone()) }}" class="rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">{{ __('Timezone') }}</label>
                            <select name="timezone" class="rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                @foreach(['America/New_York' => 'Eastern Time', 'America/Chicago' => 'Central Time', 'America/Denver' => 'Mountain Time', 'America/Los_Angeles' => 'Pacific Time', 'America/Mexico_City' => 'Mexico City', 'Europe/Madrid' => 'Madrid', 'UTC' => 'UTC'] as $tz => $label)
                                    <option value="{{ $tz }}" {{ old('timezone', $post->timezone) === $tz ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            @endif

            <div class="flex items-center justify-end space-x-4">
                <a href="{{ route('posts.show', $post) }}" class="px-4 py-2 text-gray-700 font-medium hover:text-gray-900">
                    {{ __('Cancel') }}
                </a>
                <button type="submit" class="px-6 py-2 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 transition">
                    {{ __('Update Post') }}
                </button>
            </div>
        </form>
    </div>

    <script>
        function postComposer() {
            return {
                content: @json(old('content', $post->content)),
                removeMedia: false,
                selectedChannels: @json(array_values(array_map('intval', (array) old('channels', $selectedChannels)))),
                channelError: @json($errors->has('channels')),

                validateChannels(event) {
                    if (this.selectedChannels.length > 0) {
                        this.channelError = false;
                        return;
                    }

                    event.preventDefault();
                    this.channelError = true;
                    this.$refs.channelsSection?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }
        }
    </script>
</x-layouts.app>
