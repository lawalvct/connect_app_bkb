<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Admin Login Test - ConnectApp</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'primary': '#A20030',
                        'primary-light': '#A200302B',
                        'background': '#FAFAFA'
                    }
                }
            }
        }
    </script>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-background min-h-screen">

    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">

            <!-- Header -->
            <div class="text-center">
                <h2 class="mt-6 text-3xl font-extrabold text-gray-900">Admin Portal (Debug)</h2>
                <p class="mt-2 text-sm text-gray-600">Simple test form</p>
            </div>

            <!-- Debug Info -->
            <div class="bg-blue-50 border border-blue-200 rounded-md p-4">
                <p><strong>Route:</strong> {{ route('admin.auth.login.post') }}</p>
                <p><strong>CSRF Token:</strong> {{ csrf_token() }}</p>
                <p><strong>Method:</strong> POST</p>
            </div>

            <!-- Login Form -->
            <form method="POST" action="{{ route('admin.auth.login.post') }}"
                  onsubmit="alert('Form submitted!'); return true;"
                  class="mt-8 space-y-6">
                @csrf

                @if (session('message'))
                    <div class="bg-blue-50 border border-blue-200 rounded-md p-4">
                        <p class="text-sm text-blue-700">{{ session('message') }}</p>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="bg-red-50 border border-red-200 rounded-md p-4">
                        <h3 class="text-sm font-medium text-red-800">Errors:</h3>
                        <ul class="mt-2 text-sm text-red-700">
                            @foreach ($errors->all() as $error)
                                <li>• {{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="bg-white rounded-lg shadow-md p-8">
                    <div class="space-y-6">

                        <!-- Email Field -->
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700">Email Address</label>
                            <input id="email"
                                   name="email"
                                   type="email"
                                   required
                                   value="admin@connectapp.com"
                                   class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary">
                        </div>

                        <!-- Password Field -->
                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                            <input id="password"
                                   name="password"
                                   type="password"
                                   required
                                   value="admin123"
                                   class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary">
                        </div>

                        <!-- Submit Button -->
                        <div>
                            <button type="submit"
                                    class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                                Test Login
                            </button>
                        </div>

                    </div>
                </div>

            </form>
        </div>
    </div>

    <script>
        console.log('Page loaded');
        console.log('CSRF Token:', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
        console.log('Form action:', document.querySelector('form').getAttribute('action'));
    </script>

</body>
</html>
