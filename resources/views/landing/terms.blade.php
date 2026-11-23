<x-layouts.landing :title="__('Terms of Service') . ' - HyperBit Social'">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <h1 class="text-3xl font-bold text-gray-900 mb-8">{{ __('Terms of Service') }}</h1>

        <div class="prose prose-lg max-w-none">
            <p class="text-gray-600 mb-6">{{ __('Last updated:') }} {{ date('F j, Y') }}</p>

            <h2 class="text-xl font-semibold text-gray-900 mt-8 mb-4">{{ __('1. Acceptance of Terms') }}</h2>
            <p class="text-gray-600 mb-6">{{ __('By accessing or using HyperBit Social, you agree to be bound by these Terms of Service. If you do not agree, please do not use our service.') }}</p>

            <h2 class="text-xl font-semibold text-gray-900 mt-8 mb-4">{{ __('2. Description of Service') }}</h2>
            <p class="text-gray-600 mb-6">{{ __('HyperBit Social is a social media scheduling tool that allows you to schedule and publish posts to Facebook Pages and Instagram accounts through the Meta Graph API.') }}</p>

            <h2 class="text-xl font-semibold text-gray-900 mt-8 mb-4">{{ __('3. User Responsibilities') }}</h2>
            <p class="text-gray-600 mb-4">{{ __('You are responsible for:') }}</p>
            <ul class="list-disc pl-6 text-gray-600 mb-6">
                <li>{{ __('Maintaining the security of your account') }}</li>
                <li>{{ __('All content you publish through our service') }}</li>
                <li>{{ __('Complying with Meta\'s Terms of Service and Community Standards') }}</li>
                <li>{{ __('Not using the service for spam, harassment, or illegal activities') }}</li>
            </ul>

            <h2 class="text-xl font-semibold text-gray-900 mt-8 mb-4">{{ __('4. Prohibited Uses') }}</h2>
            <p class="text-gray-600 mb-4">{{ __('You may not use HyperBit Social to:') }}</p>
            <ul class="list-disc pl-6 text-gray-600 mb-6">
                <li>{{ __('Violate any laws or regulations') }}</li>
                <li>{{ __('Infringe on intellectual property rights') }}</li>
                <li>{{ __('Post spam, misleading content, or malware') }}</li>
                <li>{{ __('Harass, abuse, or harm others') }}</li>
                <li>{{ __('Attempt to bypass rate limits or abuse the API') }}</li>
            </ul>

            <h2 class="text-xl font-semibold text-gray-900 mt-8 mb-4">{{ __('5. Service Availability') }}</h2>
            <p class="text-gray-600 mb-6">{{ __('We strive to provide reliable service but do not guarantee 100% uptime. The service depends on Meta\'s Graph API, which may have its own limitations and outages.') }}</p>

            <h2 class="text-xl font-semibold text-gray-900 mt-8 mb-4">{{ __('6. Limitation of Liability') }}</h2>
            <p class="text-gray-600 mb-6">{{ __('HyperBit Social and HyperBit IT are not liable for any indirect, incidental, or consequential damages arising from your use of the service, including but not limited to failed posts, lost content, or account suspensions by Meta.') }}</p>

            <h2 class="text-xl font-semibold text-gray-900 mt-8 mb-4">{{ __('7. Open Source License') }}</h2>
            <p class="text-gray-600 mb-6">{{ __('The HyperBit Social software is open source and distributed under the MIT License. You are free to use, modify, and distribute the software according to the license terms.') }}</p>

            <h2 class="text-xl font-semibold text-gray-900 mt-8 mb-4">{{ __('8. Changes to Terms') }}</h2>
            <p class="text-gray-600 mb-6">{{ __('We may update these terms from time to time. Continued use of the service after changes constitutes acceptance of the new terms.') }}</p>

            <h2 class="text-xl font-semibold text-gray-900 mt-8 mb-4">{{ __('9. Contact') }}</h2>
            <p class="text-gray-600 mb-6">{{ __('For questions about these Terms, contact us at:') }} <a href="mailto:legal@hyperbitit.com" class="text-indigo-600 hover:text-indigo-500">legal@hyperbitit.com</a></p>
        </div>
    </div>
</x-layouts.landing>
