<x-layouts.app :title="__('Create Post')">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-8">
            <a href="{{ route('posts.index') }}" class="text-indigo-600 hover:text-indigo-500 text-sm">
                ← {{ __('Back to Posts') }}
            </a>
            <h1 class="text-2xl font-bold text-gray-900 mt-2">{{ __('Create Post') }}</h1>
        </div>

        <form action="{{ route('posts.store') }}" method="POST" enctype="multipart/form-data" x-data="postComposer()" @submit="handleSubmit($event)">
            @csrf

            <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('Content') }}</label>
                <textarea
                    name="content"
                    rows="6"
                    x-model="content"
                    class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                    placeholder="{{ __('What do you want to share?') }}"
                    required
                >{{ old('content') }}</textarea>
                <div class="mt-2 flex items-center justify-between text-sm">
                    <div class="flex items-center space-x-4 text-gray-500">
                        <span :class="{ 'text-red-500': content.length > 63206 }">
                            <span x-text="content.length"></span>/63,206
                        </span>
                        <span>FB: <span x-text="Math.min(content.length, 63206)"></span></span>
                        <span>IG: <span x-text="Math.min(content.length, 2200)"></span>/2,200</span>
                    </div>
                </div>
                @error('content')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('Image (optional)') }}</label>
                <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-lg" x-show="!imagePreview">
                    <div class="space-y-1 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                            <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        <div class="flex text-sm text-gray-600">
                            <label class="relative cursor-pointer bg-white rounded-md font-medium text-indigo-600 hover:text-indigo-500 focus-within:outline-none">
                                <span>{{ __('Upload an image') }}</span>
                                <input type="file" name="media" class="sr-only" accept="image/jpeg,image/png,image/gif" @change="previewImage">
                            </label>
                            <p class="pl-1">{{ __('or drag and drop') }}</p>
                        </div>
                        <p class="text-xs text-gray-500">{{ __('PNG, JPG, GIF up to 10MB') }}</p>
                        <p class="text-xs text-amber-600">{{ __('Note: Instagram feed posts require an image') }}</p>
                    </div>
                </div>
                <div x-show="imagePreview" class="relative">
                    <img :src="imagePreview" class="max-h-64 rounded-lg mx-auto">
                    <button type="button" @click="removeImage" class="absolute top-2 right-2 bg-red-500 text-white rounded-full p-1 hover:bg-red-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                @error('media')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
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
                                @if($account->avatar_url)
                                    <img src="{{ $account->avatar_url }}" alt="{{ $account->platform_name }}" class="w-8 h-8 rounded-full">
                                @else
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
                                @endif
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

            <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-4">{{ __('When to publish') }}</label>

                <div class="space-y-4">
                    <label class="flex items-center">
                        <input type="radio" name="action" value="publish_now" class="w-4 h-4 text-indigo-600 focus:ring-indigo-500" {{ old('action') === 'publish_now' ? 'checked' : '' }} x-model="publishAction" :disabled="submitting">
                        <span class="ml-2 text-gray-700">{{ __('Publish now') }}</span>
                    </label>

                    <label class="flex items-center">
                        <input type="radio" name="action" value="schedule" class="w-4 h-4 text-indigo-600 focus:ring-indigo-500" {{ old('action', 'schedule') === 'schedule' ? 'checked' : '' }} x-model="publishAction" :disabled="submitting">
                        <span class="ml-2 text-gray-700">{{ __('Schedule for later') }}</span>
                    </label>

                    <div x-show="publishAction === 'schedule'" x-collapse class="ml-6 space-y-4">
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">{{ __('Date and Time') }}</label>
                            <input type="datetime-local" name="scheduled_at" value="{{ old('scheduled_at') }}" class="rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">{{ __('Timezone') }}</label>
                            <select name="timezone" class="rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                @foreach(['America/New_York' => 'Eastern Time', 'America/Chicago' => 'Central Time', 'America/Denver' => 'Mountain Time', 'America/Los_Angeles' => 'Pacific Time', 'America/Mexico_City' => 'Mexico City', 'Europe/Madrid' => 'Madrid', 'UTC' => 'UTC'] as $tz => $label)
                                    <option value="{{ $tz }}" {{ old('timezone', auth()->user()->timezone) === $tz ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                @error('scheduled_at')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center justify-end space-x-4">
                <a href="{{ route('posts.index') }}" class="px-4 py-2 text-gray-700 font-medium hover:text-gray-900" :class="{ 'pointer-events-none opacity-50': submitting }">
                    {{ __('Cancel') }}
                </a>
                <button
                    type="submit"
                    class="inline-flex items-center px-6 py-2 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 transition-colors duration-150 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 disabled:opacity-60 disabled:cursor-not-allowed"
                    :disabled="submitting"
                    :aria-busy="submitting"
                >
                    <svg x-show="submitting" x-cloak class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span x-show="!submitting && publishAction === 'publish_now'">{{ __('Publish Now') }}</span>
                    <span x-show="!submitting && publishAction === 'schedule'">{{ __('Schedule Post') }}</span>
                    <span x-show="submitting && publishAction === 'publish_now'" x-cloak>{{ __('Publishing…') }}</span>
                    <span x-show="submitting && publishAction === 'schedule'" x-cloak>{{ __('Scheduling…') }}</span>
                </button>
            </div>
            <p x-show="submitting && publishAction === 'publish_now'" x-cloak class="mt-3 text-sm text-gray-500 text-right" role="status" aria-live="polite">
                {{ __('Publishing to Meta. Keep this tab open…') }}
            </p>
        </form>
    </div>

    <script>
        function postComposer() {
            return {
                content: @json(old('content', '')),
                imagePreview: null,
                publishAction: '{{ old('action', 'schedule') }}',
                selectedChannels: @json(array_values(array_map('intval', (array) old('channels', [])))),
                channelError: @json($errors->has('channels')),
                submitting: false,

                handleSubmit(event) {
                    if (this.selectedChannels.length === 0) {
                        event.preventDefault();
                        this.channelError = true;
                        this.$refs.channelsSection?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        return;
                    }

                    this.channelError = false;

                    if (this.submitting) {
                        event.preventDefault();
                        return;
                    }

                    this.submitting = true;
                },

                previewImage(event) {
                    const file = event.target.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = (e) => {
                            this.imagePreview = e.target.result;
                        };
                        reader.readAsDataURL(file);
                    }
                },

                removeImage() {
                    this.imagePreview = null;
                    document.querySelector('input[name="media"]').value = '';
                }
            }
        }
    </script>
</x-layouts.app>
