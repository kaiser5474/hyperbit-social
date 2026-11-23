<x-layouts.landing :title="__('Data Deletion') . ' - HyperBit Social'">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <h1 class="text-3xl font-bold text-gray-900 mb-8">{{ __('Data Deletion') }}</h1>

        <div class="prose prose-lg max-w-none">
            <p class="text-gray-600 mb-6">{{ __('We respect your right to control your personal data. Here\'s how you can request deletion of your data from HyperBit Social.') }}</p>

            <h2 class="text-xl font-semibold text-gray-900 mt-8 mb-4">{{ __('Automatic Deletion via Facebook') }}</h2>
            <p class="text-gray-600 mb-6">{{ __('When you remove HyperBit Social from your Facebook account settings, we automatically receive a data deletion callback from Meta. This triggers the deletion of:') }}</p>
            <ul class="list-disc pl-6 text-gray-600 mb-6">
                <li>{{ __('Your Facebook access tokens') }}</li>
                <li>{{ __('All connected Facebook Pages and Instagram accounts') }}</li>
                <li>{{ __('All scheduled and published posts') }}</li>
                <li>{{ __('Your account preferences') }}</li>
            </ul>

            <h2 class="text-xl font-semibold text-gray-900 mt-8 mb-4">{{ __('Manual Deletion Request') }}</h2>
            <p class="text-gray-600 mb-6">{{ __('You can also request data deletion by:') }}</p>
            <ol class="list-decimal pl-6 text-gray-600 mb-6">
                <li>{{ __('Logging into your account and disconnecting all social accounts') }}</li>
                <li>{{ __('Contacting us directly at') }} <a href="mailto:privacy@hyperbitit.com" class="text-indigo-600 hover:text-indigo-500">privacy@hyperbitit.com</a></li>
            </ol>

            <h2 class="text-xl font-semibold text-gray-900 mt-8 mb-4">{{ __('What Gets Deleted') }}</h2>
            <p class="text-gray-600 mb-4">{{ __('When we process a deletion request, we remove:') }}</p>
            <ul class="list-disc pl-6 text-gray-600 mb-6">
                <li>{{ __('Your user profile and account information') }}</li>
                <li>{{ __('All encrypted access tokens') }}</li>
                <li>{{ __('All posts and media you\'ve uploaded') }}</li>
                <li>{{ __('Your preferences and settings') }}</li>
            </ul>

            <h2 class="text-xl font-semibold text-gray-900 mt-8 mb-4">{{ __('Processing Time') }}</h2>
            <p class="text-gray-600 mb-6">{{ __('Data deletion requests are typically processed immediately. You will receive a confirmation code that you can use to check the status of your deletion request.') }}</p>

            <h2 class="text-xl font-semibold text-gray-900 mt-8 mb-4">{{ __('Check Deletion Status') }}</h2>
            <p class="text-gray-600 mb-6">{{ __('If you have a confirmation code from a data deletion request, you can check the status by visiting:') }}</p>
            <code class="block bg-gray-100 p-4 rounded-lg text-sm mb-6">{{ url('/data-deletion/status/') }}/[{{ __('your-confirmation-code') }}]</code>

            <h2 class="text-xl font-semibold text-gray-900 mt-8 mb-4">{{ __('Contact Us') }}</h2>
            <p class="text-gray-600 mb-6">{{ __('If you have any questions about data deletion, please contact us at:') }} <a href="mailto:privacy@hyperbitit.com" class="text-indigo-600 hover:text-indigo-500">privacy@hyperbitit.com</a></p>
        </div>
    </div>
</x-layouts.landing>
