<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }} - ConnectApp</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center">
    <div class="max-w-md mx-auto bg-white rounded-lg shadow-md p-8 text-center">
        @if($status === 'success')
            <div class="w-16 h-16 mx-auto mb-4 bg-green-100 rounded-full flex items-center justify-center">
                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 mb-2">{{ $title }}</h1>
            <p class="text-gray-600 mb-6">{{ $message }}</p>

            @if(isset($subscription))
                <div class="bg-green-50 border border-green-200 rounded-md p-4 mb-6">
                    <div class="text-sm text-green-800">
                        <p><strong>Subscription Plan:</strong> {{ $subscription->subscription->name ?? 'Premium' }}</p>
                        <p><strong>Amount:</strong> ${{ number_format($subscription->amount, 2) }}</p>
                        <p><strong>Expires:</strong> {{ $subscription->expires_at->format('F j, Y') }}</p>
                    </div>
                </div>
            @endif

        @elseif($status === 'cancelled')
            <div class="w-16 h-16 mx-auto mb-4 bg-yellow-100 rounded-full flex items-center justify-center">
                <svg class="w-8 h-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 mb-2">{{ $title }}</h1>
            <p class="text-gray-600 mb-6">{{ $message }}</p>

        @else
            <div class="w-16 h-16 mx-auto mb-4 bg-red-100 rounded-full flex items-center justify-center">
                <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 mb-2">{{ $title }}</h1>
            <p class="text-gray-600 mb-6">{{ $message }}</p>
        @endif

        <div class="space-y-3">
            @if($status === 'success')
                <a href="{{ $redirect_url }}"
                   class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md transition duration-200 inline-block">
                    Continue to App
                </a>
            @elseif($status === 'cancelled')
                <a href="{{ config('app.url') }}/subscriptions"
                   class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md transition duration-200 inline-block">
                    Try Again
                </a>
            @else
                <a href="{{ config('app.url') }}/support"
                   class="w-full bg-red-600 hover:bg-red-700 text-white font-medium py-2 px-4 rounded-md transition duration-200 inline-block">
                    Contact Support
                </a>
            @endif

            <a href="{{ config('app.url') }}"
               class="w-full bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium py-2 px-4 rounded-md transition duration-200 inline-block">
                Back to Home
            </a>
        </div>

        <!-- Auto-redirect after success -->
        @if($status === 'success')
        <script>
            setTimeout(function() {
                window.location.href = '{{ $redirect_url }}';
            }, 5000); // Redirect after 5 seconds
        </script>
        <p class="text-sm text-gray-500 mt-4">
            You'll be redirected automatically in 5 seconds...
        </p>
        @endif
    </div>
</body>
</html>
