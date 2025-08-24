<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin Dashboard') - ConnectApp</title>

    <!-- Preload important assets -->
    <link rel="preload" href="{{ asset('images/connect_logo.png') }}" as="image" type="image/png" fetchpriority="high">
    <link rel="preload" href="{{ asset('images/default-avatar.png') }}" as="image" type="image/png" fetchpriority="high">

    <!-- DNS prefetch for external resources -->
    <link rel="dns-prefetch" href="//cdn.tailwindcss.com">
    <link rel="dns-prefetch" href="//unpkg.com">
    <link rel="dns-prefetch" href="//cdnjs.cloudflare.com">

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

        /* Image loading optimization */
        .preload-image {
            background-color: #f3f4f6;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%236b7280'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: center;
            background-size: 24px 24px;
            transition: background-color 0.3s ease;
        }

        .preload-image img {
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .preload-image img.loaded {
            opacity: 1;
        }

        /* Logo loading optimization */
        .logo-container {
            min-height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>

    @stack('styles')
</head>
<body class="bg-background text-gray-900" x-data="{ sidebarOpen: false }">

    <!-- Sidebar -->
    <div class="fixed inset-y-0 left-0 z-50 w-64 bg-white shadow-lg transform transition-transform duration-300 ease-in-out flex flex-col"
         :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'">

        <!-- Sidebar Header -->
        <div class="flex items-center justify-center h-16 bg-primary flex-shrink-0 logo-container">
            <img src="{{ asset('images/connect_logo.png') }}"
                 alt="ConnectApp"
                 class="h-10 w-auto"
                 onload="this.classList.add('loaded')"
                 loading="eager">
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
                @if(auth('admin')->user()->canManageUsers())
                <a href="{{ route('admin.users.index') }}"
                   class="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-primary-light hover:text-primary transition-colors duration-200 {{ request()->routeIs('admin.users*') ? 'bg-primary-light text-primary' : '' }}">
                    <i class="fas fa-users w-6"></i>
                    <span class="ml-3">Users</span>
                </a>
                @endif

                <!-- Content Management -->
                @if(auth('admin')->user()->canManageContent())
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
                @endif

                <!-- Ads Management -->
                @if(auth('admin')->user()->canManageAds())
                <a href="{{ route('admin.ads.index') }}"
                   class="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-primary-light hover:text-primary transition-colors duration-200 {{ request()->routeIs('admin.ads*') ? 'bg-primary-light text-primary' : '' }}">
                    <i class="fas fa-ad w-6"></i>
                    <span class="ml-3">Advertisements</span>
                </a>
                @endif

                <!-- Subscriptions -->
                @if(auth('admin')->user()->canManageSubscriptions())
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
                @endif

                <!-- Streams -->
                @if(auth('admin')->user()->canManageStreams())
                <a href="{{ route('admin.streams.index') }}"
                   class="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-primary-light hover:text-primary  {{ request()->routeIs('admin.streams*') ? 'bg-primary-light text-primary' : '' }} transition-colors duration-200">
                    <i class="fas fa-video w-6"></i>
                    <span class="ml-3">Live Streams</span>
                </a>
                @endif

                <!-- Notifications -->
                @if(auth('admin')->user()->canSendNotifications())
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
                        <a href="{{ route('admin.notifications.subscription.index') }}"
                           class="flex items-center px-4 py-2 text-sm text-gray-600 rounded-lg hover:bg-primary-light hover:text-primary {{ request()->routeIs('admin.notifications.subscription*') ? 'bg-primary-light text-primary' : '' }}">
                            <span>My Subscription</span>
                        </a>
                        @if(auth('admin')->user()->hasPermission('manage_email_templates'))
                        <a href="{{ route('admin.notifications.email.index') }}"
                           class="flex items-center px-4 py-2 text-sm text-gray-600 rounded-lg hover:bg-primary-light hover:text-primary {{ request()->routeIs('admin.notifications.email*') ? 'bg-primary-light text-primary' : '' }}">
                            <span>Email Notification</span>
                        </a>
                        @endif
                        @if(auth('admin')->user()->hasPermission('view_notification_logs'))
                        <a href="{{ route('admin.notifications.logs.index') }}"
                           class="flex items-center px-4 py-2 text-sm text-gray-600 rounded-lg hover:bg-primary-light hover:text-primary {{ request()->routeIs('admin.notifications.logs*') ? 'bg-primary-light text-primary' : '' }}">
                            <span>Notification Logs</span>
                        </a>
                        @endif
                    </div>
                </div>
                @endif

                <!-- Analytics -->
                @if(auth('admin')->user()->canViewAnalytics())
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
                        {{-- @if(auth('admin')->user()->hasPermission('view_user_analytics'))
                        <a href="{{ route('admin.analytics.users') }}"
                           class="flex items-center px-4 py-2 text-sm text-gray-600 rounded-lg hover:bg-primary-light hover:text-primary {{ request()->routeIs('admin.analytics.users') ? 'bg-primary-light text-primary' : '' }}">
                            <span>Users</span>
                        </a>
                        @endif --}}
                        {{-- @if(auth('admin')->user()->hasPermission('view_content_analytics'))
                        <a href="{{ route('admin.analytics.content') }}"
                           class="flex items-center px-4 py-2 text-sm text-gray-600 rounded-lg hover:bg-primary-light hover:text-primary {{ request()->routeIs('admin.analytics.content') ? 'bg-primary-light text-primary' : '' }}">
                            <span>Content</span>
                        </a>
                        @endif --}}
                        {{-- @if(auth('admin')->user()->hasPermission('view_revenue_analytics'))
                        <a href="{{ route('admin.analytics.revenue') }}"
                           class="flex items-center px-4 py-2 text-sm text-gray-600 rounded-lg hover:bg-primary-light hover:text-primary {{ request()->routeIs('admin.analytics.revenue') ? 'bg-primary-light text-primary' : '' }}">
                            <span>Revenue</span>
                        </a>
                        @endif --}}
                        {{-- @if(auth('admin')->user()->hasPermission('view_advertising_analytics'))
                        <a href="{{ route('admin.analytics.advertising') }}"
                           class="flex items-center px-4 py-2 text-sm text-gray-600 rounded-lg hover:bg-primary-light hover:text-primary {{ request()->routeIs('admin.analytics.advertising') ? 'bg-primary-light text-primary' : '' }}">
                            <span>Advertising</span>
                        </a>
                        @endif --}}
                        {{-- @if(auth('admin')->user()->hasPermission('view_streaming_analytics'))
                        <a href="{{ route('admin.analytics.streaming') }}"
                           class="flex items-center px-4 py-2 text-sm text-gray-600 rounded-lg hover:bg-primary-light hover:text-primary {{ request()->routeIs('admin.analytics.streaming') ? 'bg-primary-light text-primary' : '' }}">
                            <span>Streaming</span>
                        </a>
                        @endif --}}
                    </div>
                </div>
                @endif

                <!-- Settings -->
                @if(auth('admin')->user()->hasRole('super_admin') || auth('admin')->user()->canManageAdmins())
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
                        @if(auth('admin')->user()->hasRole('super_admin'))
                        <a href="{{ route('admin.settings.index') }}" class="flex items-center px-4 py-2 text-sm text-gray-600 rounded-lg hover:bg-primary-light hover:text-primary {{ request()->routeIs('admin.settings*') ? 'bg-primary-light text-primary' : '' }}">
                            <span>System Settings</span>
                        </a>
                        @endif
                        @if(auth('admin')->user()->canManageAdmins() && Route::has('admin.admins.index'))
                        <a href="{{ route('admin.admins.index') }}"
                           class="flex items-center px-4 py-2 text-sm text-gray-600 rounded-lg hover:bg-primary-light hover:text-primary {{ request()->routeIs('admin.admins*') ? 'bg-primary-light text-primary' : '' }}">
                            <span>Admin Management</span>
                        </a>
                        @endif
                    </div>
                </div>
                @endif

            </div>
        </nav>
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
                        <div class="relative" x-data="{ showNotifications: false }">
    <!-- Pusher JS for real-time notifications -->
    <script src="https://js.pusher.com/7.2/pusher.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Enable pusher logging - remove in production
            Pusher.logToConsole = true;
            var pusher = new Pusher("{{ env('PUSHER_APP_KEY') }}", {
                cluster: "{{ env('PUSHER_APP_CLUSTER') }}",
                encrypted: true
            });
            console.log('Pusher initialized');
            var channel = pusher.subscribe('admin-notifications');
            channel.bind('pusher:subscription_succeeded', function() {
                console.log('Subscribed to admin-notifications channel');
            });
            channel.bind('new-notification', function(data) {
                console.log('Received new-notification event:', data);
                             // Find notification badge and increment
                var badge = document.querySelector('.fa-bell').parentElement.querySelector('span');
                if (badge) {
                    let count = parseInt(badge.textContent) || 0;
                    badge.textContent = count + 1;
                    badge.style.display = '';
                }
                // Prepend new notification to the modal list
                var container = document.querySelector('.p-6.space-y-4.max-h-96.overflow-y-auto');
                if (container) {
                    var a = document.createElement('a');
                    a.href = data.action_url || '#';
                    a.className = 'flex items-start space-x-3 hover:bg-gray-50 rounded-lg p-2 transition';
                    a.innerHTML = `
                        <div class=\"flex-shrink-0\">
                            <span class=\"inline-flex items-center justify-center h-10 w-10 rounded-full bg-primary-light\">
                                <i class=\"fas fa-${data.icon || 'bell'} text-primary\"></i>
                            </span>
                        </div>
                        <div>
                            <p class=\"text-sm font-medium text-gray-900\">${data.title}</p>
                            <p class=\"text-xs text-gray-500\">${data.message}</p>
                            <span class=\"text-xs text-gray-400\">just now</span>
                        </div>
                    `;
                    container.prepend(a);
                }
            });
        });
    </script>
                            @php
                                use App\Models\AdminNotification;
                                $admin = auth('admin')->user();
                                $notifications = AdminNotification::forAdmin($admin->id)
                                    ->where('is_read', false)
                                    ->orderByDesc('created_at')
                                    ->limit(20)
                                    ->get();
                                $unreadCount = $notifications->where('is_read', false)->count();
                            @endphp
                <button class="p-2 text-gray-400 hover:text-gray-500 hover:bg-gray-100 rounded-lg focus:outline-none relative"
                    @click="showNotifications = !showNotifications; if (!showNotifications) { return; } window.markAllAdminNotificationsRead();">
                            <script>
                                window.markAllAdminNotificationsRead = function() {
                                    fetch("{{ route('admin.notifications.markAllRead') }}", {
                                        method: 'POST',
                                        headers: {
                                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                            'Accept': 'application/json',
                                        },
                                    }).then(response => {
                                        if (response.ok) {
                                            // Optionally reload or update notification badge/UI
                                         //   window.location.reload();
                                        }
                                    });
                                }
                            </script>
                                <i class="fas fa-bell text-xl"></i>
                                @if($unreadCount > 0)
                                    <span class="absolute -top-1 -right-1 inline-flex items-center justify-center px-1.5 py-0.5 text-xs font-bold leading-none text-white bg-red-600 rounded-full border-2 border-white shadow" style="min-width: 1.5em; min-height: 1.5em;">{{$unreadCount}}</span>
                                @endif
                            </button>
                            <!-- Notification Modal -->
                            <div x-show="showNotifications" x-cloak
                                 class="fixed inset-0 z-50 flex items-start justify-end"
                                 style="background: rgba(31, 41, 55, 0.3);"
                                 @click.away="showNotifications = false">
                                <div class="mt-20 mr-8 w-96 bg-white rounded-lg shadow-xl border border-gray-200 overflow-hidden"
                                     @click.stop>
                                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 bg-primary">
                                        <h3 class="text-lg font-semibold text-white">Notifications</h3>
                                        <button class="text-white hover:text-gray-200 focus:outline-none" @click="showNotifications = false">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                    <div class="p-6 space-y-4 max-h-96 overflow-y-auto">
                                        @forelse($notifications as $notification)
                                            <a href="{{ $notification->action_url ?: '#' }}" class="flex items-start space-x-3 hover:bg-gray-50 rounded-lg p-2 transition" @if($notification->is_read) style="opacity:0.7;" @endif>
                                                <div class="flex-shrink-0">
                                                    <span class="inline-flex items-center justify-center h-10 w-10 rounded-full bg-primary-light">
                                                        <i class="fas fa-{{ $notification->icon ?? 'bell' }} {{ $notification->type_color ?? 'text-primary' }}"></i>
                                                    </span>
                                                </div>
                                                <div>
                                                    <p class="text-sm font-medium text-gray-900">{{ $notification->title }}</p>
                                                    <p class="text-xs text-gray-500">{{ $notification->message }}</p>
                                                    <span class="text-xs text-gray-400">{{ $notification->time_ago }}</span>
                                                </div>
                                            </a>
                                        @empty
                                            <div class="text-center text-gray-400 py-8">
                                                <i class="fas fa-bell-slash text-3xl mb-2"></i>
                                                <div>No notifications found.</div>
                                            </div>
                                        @endforelse
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Admin Profile Info -->
                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open"
                                    class="flex items-center space-x-3 p-2 text-gray-700 hover:bg-gray-100 rounded-lg transition-colors duration-200">
                                <!-- Admin Profile Image -->
                                @if(auth('admin')->user()->profile_image)
                                    <img src="{{ Storage::url(auth('admin')->user()->profile_image) }}"
                                         alt="Profile Image"
                                         class="w-8 h-8 rounded-full object-cover border-2 border-primary">
                                @else
                                    <div class="w-8 h-8 rounded-full bg-primary flex items-center justify-center text-white font-bold text-sm">
                                        {{ strtoupper(substr(auth('admin')->user()->name, 0, 1)) }}
                                    </div>
                                @endif

                                <!-- Admin Info -->
                                <div class="text-left hidden sm:block">
                                    <p class="text-sm font-medium text-gray-900">{{ auth('admin')->user()->name }}</p>
                                    <p class="text-xs text-gray-500">{{ auth('admin')->user()->getRoleDisplayName() }}</p>
                                </div>

                                <!-- Dropdown Icon -->
                                <i class="fas fa-chevron-down text-xs text-gray-400"></i>
                            </button>

                            <div x-show="open" @click.away="open = false"
                                 class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50 border border-gray-200">
                                <a href="{{ route('admin.profile.index') }}" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
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

    <!-- Preload optimization script -->
    <script>
        // Enhanced image preloading with visual feedback
        document.addEventListener('DOMContentLoaded', function() {
            // Check if preloaded images are cached
            const preloadedImages = [
                '{{ asset("images/connect_logo.png") }}',
                '{{ asset("images/default-avatar.png") }}'
            ];

            preloadedImages.forEach(function(src) {
                const img = new Image();
                img.onload = function() {
                    console.log('✅ Preloaded:', src);
                };
                img.onerror = function() {
                    console.warn('❌ Failed to preload:', src);
                };
                img.src = src;
            });

            // Add loading class removal for better UX
            const allImages = document.querySelectorAll('img');
            allImages.forEach(function(img) {
                if (img.complete) {
                    img.classList.add('loaded');
                } else {
                    img.addEventListener('load', function() {
                        this.classList.add('loaded');
                    });
                }
            });
        });

        // Performance monitoring for preloaded resources
        if (typeof performance !== 'undefined' && performance.getEntriesByType) {
            window.addEventListener('load', function() {
                setTimeout(function() {
                    const entries = performance.getEntriesByType('resource');
                    const preloadedResources = entries.filter(entry =>
                        entry.name.includes('connect_logo.png') ||
                        entry.name.includes('default-avatar.png')
                    );

                    console.log('🚀 Preloaded resources timing:', preloadedResources);
                }, 1000);
            });
        }
    </script>

    @stack('scripts')
</body>
</html>
