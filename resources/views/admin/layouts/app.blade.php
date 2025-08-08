<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin Dashboard') - ConnectApp</title>

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

    <!-- Alpine.js -->
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <!-- Custom Scrollbar Styles -->
    <style>
        /* Custom scrollbar for sidebar navigation */
        .sidebar-scroll::-webkit-scrollbar {
            width: 4px;
        }

        .sidebar-scroll::-webkit-scrollbar-track {
            background: transparent;
        }

        .sidebar-scroll::-webkit-scrollbar-thumb {
            background: #A200302B;
            border-radius: 2px;
        }

        .sidebar-scroll::-webkit-scrollbar-thumb:hover {
            background: #A20030;
        }

        /* Firefox scrollbar */
        .sidebar-scroll {
            scrollbar-width: thin;
            scrollbar-color: #A200302B transparent;
        }
    </style>

    @stack('styles')
</head>
<body class="bg-background text-gray-900" x-data="{ sidebarOpen: false }">

    <!-- Sidebar -->
    <div class="fixed inset-y-0 left-0 z-50 w-64 bg-white shadow-lg transform transition-transform duration-300 ease-in-out flex flex-col"
         :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'">

        <!-- Sidebar Header -->
        <div class="flex items-center justify-center h-16 bg-primary flex-shrink-0">
            <img src="{{ asset('images/connect_logo.png') }}" alt="ConnectApp" class="h-10 w-auto">
            <span class="ml-2 text-white text-xl font-bold">Admin</span>
        </div>

        <!-- Navigation - Scrollable -->
        <nav class="flex-1 overflow-y-auto py-4 sidebar-scroll">
            <div class="px-4 space-y-2">

                <!-- Dashboard -->
                <a href="{{ route('admin.dashboard') }}"
                   class="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-primary-light hover:text-primary transition-colors duration-200 {{ request()->routeIs('admin.dashboard*') ? 'bg-primary-light text-primary' : '' }}">
                    <i class="fas fa-tachometer-alt w-6"></i>
                    <span class="ml-3">Dashboard</span>
                </a>

                <!-- User Management -->
                <a href="{{ route('admin.users.index') }}"
                   class="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-primary-light hover:text-primary transition-colors duration-200 {{ request()->routeIs('admin.users*') ? 'bg-primary-light text-primary' : '' }}">
                    <i class="fas fa-users w-6"></i>
                    <span class="ml-3">Users</span>
                </a>

                <!-- Content Management -->
                <div x-data="{ open: {{ request()->routeIs('admin.posts*') || request()->routeIs('admin.stories*') ? 'true' : 'false' }} }">
                    <button @click="open = !open"
                            class="w-full flex items-center justify-between px-4 py-3 text-gray-700 rounded-lg hover:bg-primary-light hover:text-primary transition-colors duration-200">
                        <div class="flex items-center">
                            <i class="fas fa-newspaper w-6"></i>
                            <span class="ml-3">Content</span>
                        </div>
                        <i class="fas fa-chevron-down transform transition-transform duration-200" :class="open ? 'rotate-180' : ''"></i>
                    </button>
                    <div x-show="open" x-collapse class="ml-6 mt-2 space-y-2">
                        <a href="{{ route('admin.posts.index') }}"
                           class="flex items-center px-4 py-2 text-sm text-gray-600 rounded-lg hover:bg-primary-light hover:text-primary {{ request()->routeIs('admin.posts*') ? 'bg-primary-light text-primary' : '' }}">
                            <span>Posts</span>
                        </a>
                        <a href="{{ route('admin.stories.index') }}"
                           class="flex items-center px-4 py-2 text-sm text-gray-600 rounded-lg hover:bg-primary-light hover:text-primary {{ request()->routeIs('admin.stories*') ? 'bg-primary-light text-primary' : '' }}">
                            <span>Stories</span>
                        </a>
                    </div>
                </div>

                <!-- Ads Management -->
                <a href="{{ route('admin.ads.index') }}"
                   class="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-primary-light hover:text-primary transition-colors duration-200 {{ request()->routeIs('admin.ads*') ? 'bg-primary-light text-primary' : '' }}">
                    <i class="fas fa-ad w-6"></i>
                    <span class="ml-3">Advertisements</span>
                </a>

                <!-- Subscriptions -->
                <div x-data="{ open: {{ request()->routeIs('admin.subscriptions*') ? 'true' : 'false' }} }">
                    <button @click="open = !open"
                            class="w-full flex items-center justify-between px-4 py-3 text-gray-700 rounded-lg hover:bg-primary-light hover:text-primary transition-colors duration-200">
                        <div class="flex items-center">
                            <i class="fas fa-crown w-6"></i>
                            <span class="ml-3">Subscriptions</span>
                        </div>
                        <i class="fas fa-chevron-down transform transition-transform duration-200" :class="open ? 'rotate-180' : ''"></i>
                    </button>
                    <div x-show="open" x-collapse class="ml-6 mt-2 space-y-2">
                        <a href="{{ route('admin.subscriptions.index') }}"
                           class="flex items-center px-4 py-2 text-sm text-gray-600 rounded-lg hover:bg-primary-light hover:text-primary {{ request()->routeIs('admin.subscriptions.index') || request()->routeIs('admin.subscriptions.show') ? 'bg-primary-light text-primary' : '' }}">
                            <span>User Subscriptions</span>
                        </a>
                        <a href="{{ route('admin.subscriptions.plans.index') }}"
                           class="flex items-center px-4 py-2 text-sm text-gray-600 rounded-lg hover:bg-primary-light hover:text-primary {{ request()->routeIs('admin.subscriptions.plans*') ? 'bg-primary-light text-primary' : '' }}">
                            <span>Subscription Plans</span>
                        </a>
                    </div>
                </div>

                <!-- Streams -->
                <a href="{{ route('admin.streams.index') }}"
                   class="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-primary-light hover:text-primary  {{ request()->routeIs('admin.streams*') ? 'bg-primary-light text-primary' : '' }} transition-colors duration-200">
                    <i class="fas fa-video w-6"></i>
                    <span class="ml-3">Live Streams</span>
                </a>

                <!-- Notifications -->
                <div x-data="{ open: {{ request()->routeIs('admin.notifications*') ? 'true' : 'false' }} }">
                    <button @click="open = !open"
                            class="w-full flex items-center justify-between px-4 py-3 text-gray-700 rounded-lg hover:bg-primary-light hover:text-primary transition-colors duration-200">
                        <div class="flex items-center">
                            <i class="fas fa-bell w-6"></i>
                            <span class="ml-3">Notifications</span>
                        </div>
                        <i class="fas fa-chevron-down transform transition-transform duration-200" :class="open ? 'rotate-180' : ''"></i>
                    </button>
                    <div x-show="open" x-collapse class="ml-6 mt-2 space-y-2">
                        <a href="{{ route('admin.notifications.push.index') }}"
                           class="flex items-center px-4 py-2 text-sm text-gray-600 rounded-lg hover:bg-primary-light hover:text-primary {{ request()->routeIs('admin.notifications.push*') ? 'bg-primary-light text-primary' : '' }}">
                            <span>Push Notifications</span>
                        </a>
                        <a href="{{ route('admin.notifications.email.index') }}"
                           class="flex items-center px-4 py-2 text-sm text-gray-600 rounded-lg hover:bg-primary-light hover:text-primary {{ request()->routeIs('admin.notifications.email*') ? 'bg-primary-light text-primary' : '' }}">
                            <span>Email Templates</span>
                        </a>
                        {{-- <a href="{{ route('admin.notifications.sms.index') }}"
                           class="flex items-center px-4 py-2 text-sm text-gray-600 rounded-lg hover:bg-primary-light hover:text-primary {{ request()->routeIs('admin.notifications.sms*') ? 'bg-primary-light text-primary' : '' }}">
                            <span>SMS Settings</span>
                        </a> --}}
                        <a href="{{ route('admin.notifications.logs.index') }}"
                           class="flex items-center px-4 py-2 text-sm text-gray-600 rounded-lg hover:bg-primary-light hover:text-primary {{ request()->routeIs('admin.notifications.logs*') ? 'bg-primary-light text-primary' : '' }}">
                            <span>Notification Logs</span>
                        </a>
                    </div>
                </div>

                <!-- Analytics -->
                <div x-data="{ open: {{ request()->routeIs('admin.analytics*') ? 'true' : 'false' }} }">
                    <button @click="open = !open"
                            class="w-full flex items-center justify-between px-4 py-3 text-gray-700 rounded-lg hover:bg-primary-light hover:text-primary transition-colors duration-200">
                        <div class="flex items-center">
                            <i class="fas fa-chart-bar w-6"></i>
                            <span class="ml-3">Analytics</span>
                        </div>
                        <i class="fas fa-chevron-down transform transition-transform duration-200" :class="open ? 'rotate-180' : ''"></i>
                    </button>
                    <div x-show="open" x-collapse class="ml-6 mt-2 space-y-2">
                        <a href="{{ route('admin.analytics.index') }}"
                           class="flex items-center px-4 py-2 text-sm text-gray-600 rounded-lg hover:bg-primary-light hover:text-primary {{ request()->routeIs('admin.analytics.index') ? 'bg-primary-light text-primary' : '' }}">
                            <span>Overview</span>
                        </a>
                        <a href="{{ route('admin.analytics.users') }}"
                           class="flex items-center px-4 py-2 text-sm text-gray-600 rounded-lg hover:bg-primary-light hover:text-primary {{ request()->routeIs('admin.analytics.users') ? 'bg-primary-light text-primary' : '' }}">
                            <span>Users</span>
                        </a>
                        <a href="{{ route('admin.analytics.content') }}"
                           class="flex items-center px-4 py-2 text-sm text-gray-600 rounded-lg hover:bg-primary-light hover:text-primary {{ request()->routeIs('admin.analytics.content') ? 'bg-primary-light text-primary' : '' }}">
                            <span>Content</span>
                        </a>
                        <a href="{{ route('admin.analytics.revenue') }}"
                           class="flex items-center px-4 py-2 text-sm text-gray-600 rounded-lg hover:bg-primary-light hover:text-primary {{ request()->routeIs('admin.analytics.revenue') ? 'bg-primary-light text-primary' : '' }}">
                            <span>Revenue</span>
                        </a>
                        <a href="{{ route('admin.analytics.advertising') }}"
                           class="flex items-center px-4 py-2 text-sm text-gray-600 rounded-lg hover:bg-primary-light hover:text-primary {{ request()->routeIs('admin.analytics.advertising') ? 'bg-primary-light text-primary' : '' }}">
                            <span>Advertising</span>
                        </a>
                        <a href="{{ route('admin.analytics.streaming') }}"
                           class="flex items-center px-4 py-2 text-sm text-gray-600 rounded-lg hover:bg-primary-light hover:text-primary {{ request()->routeIs('admin.analytics.streaming') ? 'bg-primary-light text-primary' : '' }}">
                            <span>Streaming</span>
                        </a>
                    </div>
                </div>

                <!-- Settings -->
                <div x-data="{ open: {{ request()->routeIs('admin.admins*') ? 'true' : 'false' }} }">
                    <button @click="open = !open"
                            class="w-full flex items-center justify-between px-4 py-3 text-gray-700 rounded-lg hover:bg-primary-light hover:text-primary transition-colors duration-200">
                        <div class="flex items-center">
                            <i class="fas fa-cog w-6"></i>
                            <span class="ml-3">Settings</span>
                        </div>
                        <i class="fas fa-chevron-down transform transition-transform duration-200" :class="open ? 'rotate-180' : ''"></i>
                    </button>
                    <div x-show="open" x-collapse class="ml-6 mt-2 space-y-2">
                        <a href="#" class="flex items-center px-4 py-2 text-sm text-gray-600 rounded-lg hover:bg-primary-light hover:text-primary">
                            <span>System Settings</span>
                        </a>
                        @if(auth('admin')->user() && (auth('admin')->user()->hasRole('super_admin') || auth('admin')->user()->hasRole('admin')) && Route::has('admin.admins.index'))
                        <a href="{{ route('admin.admins.index') }}"
                           class="flex items-center px-4 py-2 text-sm text-gray-600 rounded-lg hover:bg-primary-light hover:text-primary {{ request()->routeIs('admin.admins*') ? 'bg-primary-light text-primary' : '' }}">
                            <span>Admin Management</span>
                        </a>
                        @endif
                    </div>
                </div>

            </div>
        </nav>

        <!-- Admin Info - Fixed at bottom -->
        <div class="flex-shrink-0 p-4 border-t border-gray-200 bg-white">
            <div class="flex items-center">
                <div class="w-10 h-10 rounded-full bg-primary flex items-center justify-center text-white font-bold">
                    {{ strtoupper(substr(auth('admin')->user()->name, 0, 1)) }}
                </div>
                <div class="ml-3 flex-1">
                    <p class="text-sm font-medium text-gray-900">{{ auth('admin')->user()->name }}</p>
                    <p class="text-xs text-gray-500">{{ auth('admin')->user()->getRoleDisplayName() }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="lg:ml-64">
        <!-- Top Navigation -->
        <header class="bg-white shadow-sm border-b border-gray-200">
            <div class="px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <!-- Mobile menu button -->
                    <button @click="sidebarOpen = !sidebarOpen"
                            class="lg:hidden p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100">
                        <i class="fas fa-bars text-xl"></i>
                    </button>

                    <!-- Page Title -->
                    <div class="flex-1 lg:flex-none">
                        <h1 class="text-2xl font-semibold text-gray-900">@yield('page-title', 'Dashboard')</h1>
                    </div>

                    <!-- Right side buttons -->
                    <div class="flex items-center space-x-4">
                        <!-- Notifications -->
                        <button class="p-2 text-gray-400 hover:text-gray-500 hover:bg-gray-100 rounded-lg">
                            <i class="fas fa-bell text-xl"></i>
                        </button>

                        <!-- Profile Dropdown -->
                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open"
                                    class="flex items-center p-2 text-gray-400 hover:text-gray-500 hover:bg-gray-100 rounded-lg">
                                <i class="fas fa-user-circle text-xl"></i>
                            </button>

                            <div x-show="open" @click.away="open = false"
                                 class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50">
                                <a href="#" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    <i class="fas fa-user w-4 mr-3"></i>
                                    Profile Settings
                                </a>
                                <form method="POST" action="{{ route('admin.auth.logout') }}">
                                    @csrf
                                    <button type="submit" class="w-full flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                        <i class="fas fa-sign-out-alt w-4 mr-3"></i>
                                        Logout
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Page Content -->
        <main class="p-6">
            <!-- Page Header -->
            @hasSection('header')
                <div class="mb-6">
                    @yield('header')
                </div>
            @endif

            <!-- Flash Messages -->
            @if(session('success'))
                <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg">
                    {{ session('error') }}
                </div>
            @endif

            @if(session('message'))
                <div class="mb-4 p-4 bg-blue-100 border border-blue-400 text-blue-700 rounded-lg">
                    {{ session('message') }}
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    <!-- Mobile Sidebar Overlay -->
    <div x-show="sidebarOpen" @click="sidebarOpen = false"
         class="fixed inset-0 bg-gray-600 bg-opacity-75 z-40 lg:hidden"></div>

    @stack('scripts')
</body>
</html>
