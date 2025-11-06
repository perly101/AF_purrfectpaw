<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <!-- Styles -->
    <link rel="stylesheet" href="{{ mix('css/app.css') }}">
    
    <!-- Custom Responsive CSS -->
    <link rel="stylesheet" href="{{ asset('css/responsive.css') }}">

    <!-- Scripts -->
    <script src="{{ mix('js/app.js') }}" defer></script>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- Alpine.js -->
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <!-- Notifications Script (clinic only) -->
    @if(auth()->check() && auth()->user()->role === 'clinic')
        <script src="{{ asset('js/enhanced-notification-sound.js') }}"></script>
        <script src="{{ asset('js/global-notifications.js') }}"></script>
        <script src="{{ asset('js/notifications.js') }}"></script>
    @endif
    
    <!-- Additional Responsive Styles -->
    <style>
        @media (max-width: 768px) {
            .md\:hidden-custom {
                display: none !important;
            }
            .md\:block-custom {
                display: block !important;
            }
            .md\:flex-col-custom {
                flex-direction: column !important;
            }
        }
        @media (max-width: 640px) {
            .sm\:px-4-custom {
                padding-left: 1rem !important;
                padding-right: 1rem !important;
            }
            .sm\:text-sm-custom {
                font-size: 0.875rem !important;
            }
        }
    </style>
</head>
<body class="font-sans antialiased">
    <!-- Global Notification Container (only for clinic users) -->
    @if(auth()->check() && auth()->user()->role === 'clinic')
        <div id="global-notification-container" class="fixed top-0 right-0 z-50 p-4 w-full md:w-80 pointer-events-none"></div>
        <div id="global-popup-notification-container" class="fixed bottom-4 right-4 z-50 pointer-events-none"></div>
        <audio id="global-notification-sound" src="{{ asset('sounds/noti.mp3') }}" preload="auto" style="display: none;"></audio>
        
        <!-- Floating Notification Button - Bottom Right -->
        @php
            $clinic = \App\Models\ClinicInfo::where('user_id', auth()->id())->first();
            $unreadNotificationsCount = $clinic ? $clinic->notifications()->whereNull('read_at')->count() : 0;
        @endphp
        <div class="fixed bottom-6 right-6 z-40" x-data="{ showDropdown: false }">
            <!-- Floating Notification Button -->
            <button @click="showDropdown = !showDropdown" 
                    class="relative w-14 h-14 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white rounded-full shadow-lg hover:shadow-xl transition-all duration-200 transform hover:scale-105 group">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mx-auto" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M10 2a6 6 0 00-6 6v3.586l-.707.707A1 1 0 004 14h12a1 1 0 00.707-1.707L16 11.586V8a6 6 0 00-6-6zM10 18a3 3 0 01-3-3h6a3 3 0 01-3 3z" />
                </svg>
                
                <!-- Notification Badge -->
                @if($unreadNotificationsCount > 0)
                    <span class="absolute -top-1 -right-1 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white bg-red-600 rounded-full animate-pulse border-2 border-white">
                        {{ $unreadNotificationsCount > 99 ? '99+' : $unreadNotificationsCount }}
                    </span>
                @endif
                
                <!-- Ripple Effect -->
                <span class="absolute inset-0 w-full h-full bg-white opacity-0 rounded-full transform scale-0 group-hover:scale-100 group-hover:opacity-10 transition-all duration-200"></span>
            </button>
            
            <!-- Notification Dropdown -->
            <div x-show="showDropdown" 
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 transform scale-95 translate-y-2"
                 x-transition:enter-end="opacity-100 transform scale-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 transform scale-100 translate-y-0"
                 x-transition:leave-end="opacity-0 transform scale-95 translate-y-2"
                 @click.away="showDropdown = false"
                 class="absolute bottom-16 right-0 w-80 bg-white rounded-xl shadow-2xl border border-gray-200 z-50 overflow-hidden">
                
                <!-- Header -->
                <div class="p-4 bg-gradient-to-r from-indigo-600 to-purple-600 text-white">
                    <div class="flex items-center justify-between">
                        <h3 class="font-semibold text-lg">Notifications</h3>
                        @if($unreadNotificationsCount > 0)
                            <span class="bg-white/20 text-white text-xs px-2 py-1 rounded-full">
                                {{ $unreadNotificationsCount }} unread
                            </span>
                        @endif
                    </div>
                </div>
                
                <!-- Notifications List -->
                <div class="max-h-80 overflow-y-auto">
                    @if($clinic && $unreadNotificationsCount > 0)
                        <div class="divide-y divide-gray-100">
                            @foreach($clinic->notifications()->whereNull('read_at')->limit(5)->get() as $notification)
                                <div class="p-4 hover:bg-gray-50 transition-colors">
                                    <div class="flex items-start space-x-3">
                                        <div class="w-2 h-2 bg-indigo-600 rounded-full mt-2 flex-shrink-0"></div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm text-gray-800 font-medium">
                                                {{ $notification->data['message'] ?? 'New notification' }}
                                            </p>
                                            <p class="text-xs text-gray-500 mt-1">
                                                {{ $notification->created_at->diffForHumans() }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="p-8 text-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-gray-300 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5-5-5h5v-5a7.5 7.5 0 01-15 0v-5h5l-5-5-5 5h5v5a7.5 7.5 0 0115 0z" />
                            </svg>
                            <p class="text-gray-500 text-sm font-medium">No new notifications</p>
                            <p class="text-gray-400 text-xs mt-1">You're all caught up!</p>
                        </div>
                    @endif
                </div>
                
                <!-- Footer Actions -->
                <div class="p-3 bg-gray-50 border-t border-gray-100 flex justify-between items-center">
                    <a href="{{ route('clinic.notifications.index') }}" 
                       class="text-sm text-indigo-600 hover:text-indigo-700 font-medium transition-colors">
                        View All
                    </a>
                    <a href="{{ route('clinic.notifications.settings') }}" 
                       class="text-sm text-gray-600 hover:text-gray-700 transition-colors flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        Settings
                    </a>
                </div>
            </div>
        </div>
    @endif

    <div class="min-h-screen bg-gray-100">

        <!-- Page Heading -->
        @isset($header)
            <header class="bg-white shadow">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    {{ $header }}
                </div>
            </header>
        @endisset

        <!-- Page Content -->
        <main>
            @hasSection('content')
                @yield('content')
            @else
                @isset($slot)
                    {{ $slot }}
                @endisset
            @endif
        </main>
    </div>

    @if(session('success'))
    <script>
        Swal.fire({
            title: 'Success!',
            text: "{{ session('success') }}",
            icon: 'success',
            confirmButtonText: 'OK'
        });
    </script>
    @endif

    @if(session('error'))
    <script>
        Swal.fire({
            title: 'Error!',
            text: "{{ session('error') }}",
            icon: 'error',
            confirmButtonText: 'OK'
        });
    </script>
    @endif

    @stack('scripts')
</body>
</html>
