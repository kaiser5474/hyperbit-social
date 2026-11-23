<x-layouts.landing :title="__('Privacy Policy') . ' - HyperBit Social'">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <h1 class="text-3xl font-bold text-gray-900 mb-8">{{ __('Privacy Policy') }}</h1>

        <div class="prose prose-lg max-w-none">
            <p class="text-gray-600 mb-6">{{ __('Last updated:') }} {{ date('F j, Y') }}</p>

            <h2 class="text-xl font-semibold text-gray-900 mt-8 mb-4">{{ __('1. Information We Collect') }}</h2>
            <p class="text-gray-600 mb-4">{{ __('When you use HyperBit Social, we collect:') }}</p>
            <ul class="list-disc pl-6 text-gray-600 mb-6">
                <li>{{ __('Your Facebook profile information (name, email, profile picture)') }}</li>
                <li>{{ __('Access tokens for connected Facebook Pages and Instagram accounts') }}</li>
                <li>{{ __('Post content and media you create through our service') }}</li>
                <li>{{ __('Usage data and preferences') }}</li>
            </ul>

            <h2 class="text-xl font-semibold text-gray-900 mt-8 mb-4">{{ __('2. How We Use Your Information') }}</h2>
            <p class="text-gray-600 mb-4">{{ __('We use your information to:') }}</p>
            <ul class="list-disc pl-6 text-gray-600 mb-6">
                <li>{{ __('Authenticate you with Facebook and Instagram') }}</li>
                <li>{{ __('Publish scheduled posts to your connected accounts') }}</li>
                <li>{{ __('Provide and improve our services') }}</li>
                <li>{{ __('Send you service-related communications') }}</li>
            </ul>

            <h2 class="text-xl font-semibold text-gray-900 mt-8 mb-4">{{ __('3. Data Security') }}</h2>
            <p class="text-gray-600 mb-6">{{ __('We take security seriously. All access tokens are encrypted at rest using industry-standard encryption. We never share your credentials with third parties.') }}</p>

            <h2 class="text-xl font-semibold text-gray-900 mt-8 mb-4">{{ __('4. Data Retention') }}</h2>
            <p class="text-gray-600 mb-6">{{ __('We retain your data as long as your account is active. You can request deletion of your data at any time by using the data deletion page or disconnecting your Facebook account.') }}</p>

            <h2 class="text-xl font-semibold text-gray-900 mt-8 mb-4">{{ __('5. Third-Party Services') }}</h2>
            <p class="text-gray-600 mb-6">{{ __('We use Meta\'s Graph API to interact with Facebook and Instagram. Your use of these platforms is also subject to Meta\'s Privacy Policy and Terms of Service.') }}</p>

            <h2 class="text-xl font-semibold text-gray-900 mt-8 mb-4">{{ __('6. Your Rights') }}</h2>
            <p class="text-gray-600 mb-4">{{ __('You have the right to:') }}</p>
            <ul class="list-disc pl-6 text-gray-600 mb-6">
                <li>{{ __('Access your personal data') }}</li>
                <li>{{ __('Correct inaccurate data') }}</li>
                <li>{{ __('Request deletion of your data') }}</li>
                <li>{{ __('Disconnect your social accounts at any time') }}</li>
            </ul>

            <h2 class="text-xl font-semibold text-gray-900 mt-8 mb-4">{{ __('7. Contact Us') }}</h2>
            <p class="text-gray-600 mb-6">{{ __('If you have questions about this Privacy Policy, please contact us at:') }} <a href="mailto:privacy@hyperbitit.com" class="text-indigo-600 hover:text-indigo-500">privacy@hyperbitit.com</a></p>
        </div>
    </div>
</x-layouts.landing>
