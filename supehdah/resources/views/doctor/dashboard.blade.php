<x-app-layout>
    <!-- Doctor Notification System -->
    <div id="global-notification-container" class="fixed top-4 right-4 z-50 space-y-2" style="max-width: 350px;"></div>
    <div id="global-popup-notification-container" class="fixed bottom-4 right-4 z-50 space-y-2" style="max-width: 350px;"></div>
    <audio id="global-notification-sound" src="{{ asset('sounds/noti.mp3') }}" preload="auto"></audio>
    
    {{-- Include mobile navigation (only visible on mobile) --}}
    @include('doctor.components.mobile-nav')
    
    <div class="py-6 md:py-12 bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            {{-- Include Sidebar (hidden on mobile) --}}
            <div class="hidden md:block">
                @include('doctor.components.sidebar')
            </div>
            
            {{-- Main Content --}}
            <div class="md:ml-64 md:pl-8 mt-16 md:mt-0">
                <div class="mb-6 md:mb-8">
                    <h1 class="text-2xl md:text-3xl font-bold text-gray-800 mb-2">Welcome, Dr. {{ $doctor->first_name }}</h1>
                    <p class="text-sm md:text-base text-gray-600">Here's an overview of your medical practice</p>
                </div>
                
                @if(session('success'))
                    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-3 md:p-4 mb-4 md:mb-6 rounded shadow-sm" role="alert">
                        <div class="flex items-center">
                            <svg class="h-4 w-4 md:h-5 md:w-5 mr-2 text-green-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            <p class="text-sm md:text-base">{{ session('success') }}</p>
                        </div>
                    </div>
                @endif
                
                {{-- Stats Overview --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-6 lg:gap-8 mb-6 md:mb-8">
                    <!-- Total Patients -->
                    <div class="bg-gradient-to-br from-white to-blue-50 rounded-xl p-6 border border-blue-100 shadow-sm hover:shadow-md transition-all duration-300 transform hover:-translate-y-1">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-gradient-to-br from-blue-500 to-blue-600 text-white mr-4 shadow-lg">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-lg text-blue-800 font-medium">Total Patients</p>
                                <div class="flex items-end">
                                    <p class="text-3xl font-bold text-blue-900">{{ $totalPatients }}</p>
                                    <span class="ml-2 text-blue-600 text-sm">patients</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-6 h-1 bg-blue-100 rounded-full overflow-hidden">
                            <div class="h-1 bg-blue-500 rounded-full" style="width: 75%"></div>
                        </div>
                    </div>
                    
                    <!-- Completed Appointments -->
                    <div class="bg-gradient-to-br from-white to-green-50 rounded-xl p-6 border border-green-100 shadow-sm hover:shadow-md transition-all duration-300 transform hover:-translate-y-1">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-gradient-to-br from-green-500 to-green-600 text-white mr-4 shadow-lg">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-lg text-green-800 font-medium">Completed</p>
                                <div class="flex items-end">
                                    <p class="text-3xl font-bold text-green-900">{{ $completedAppointments }}</p>
                                    <span class="ml-2 text-green-600 text-sm">consultations</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-6 h-1 bg-green-100 rounded-full overflow-hidden">
                            <div class="h-1 bg-green-500 rounded-full" style="width: 65%"></div>
                        </div>
                    </div>
                    
                    <!-- Pending Consultations -->
                    <div class="bg-gradient-to-br from-white to-amber-50 rounded-xl p-6 border border-amber-100 shadow-sm hover:shadow-md transition-all duration-300 transform hover:-translate-y-1">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-gradient-to-br from-amber-500 to-amber-600 text-white mr-4 shadow-lg">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-lg text-amber-800 font-medium">Pending</p>
                                <div class="flex items-end">
                                    <p class="text-3xl font-bold text-amber-900">{{ $pendingConsultations }}</p>
                                    <span class="ml-2 text-amber-600 text-sm">waiting</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-6 h-1 bg-amber-100 rounded-full overflow-hidden">
                            <div class="h-1 bg-amber-500 rounded-full" style="width: 40%"></div>
                        </div>
                    </div>
                </div>
                    
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    {{-- Today's Appointments --}}
                    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-indigo-50 flex justify-between items-center">
                            <div class="flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-600 mr-2" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" />
                                </svg>
                                <h2 class="text-lg font-medium text-gray-800">Today's Appointments</h2>
                            </div>
                            <div class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full font-semibold">
                                {{ $todayAppointments->count() }} Scheduled
                            </div>
                            </div>
                        </div>
                        <div class="divide-y divide-gray-100">
                            @if($todayAppointments->count() > 0)
                                @foreach($todayAppointments as $appointment)
                                    <div class="p-4 hover:bg-blue-50/30 transition-colors duration-200">
                                        <div class="flex justify-between items-start">
                                            <div class="flex items-center">
                                                <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-700 font-bold mr-3">
                                                    {{ substr($appointment->owner_name, 0, 1) }}
                                                </div>
                                                <div>
                                                    <h3 class="font-medium text-gray-900">{{ $appointment->owner_name }}</h3>
                                                    <div class="flex items-center text-sm text-gray-500">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                                                        </svg>
                                                        {{ $appointment->appointment_time ? \Carbon\Carbon::parse($appointment->appointment_time)->format('h:i A') : 'No time set' }}
                                                    </div>
                                                </div>
                                            </div>
                                            <div>
                                                <span class="inline-flex px-2.5 py-1 text-xs font-semibold rounded-full
                                                    {{ $appointment->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                                    {{ $appointment->status === 'assigned' ? 'bg-purple-100 text-purple-800' : '' }}
                                                    {{ $appointment->status === 'confirmed' ? 'bg-blue-100 text-blue-800' : '' }}
                                                    {{ $appointment->status === 'in_progress' ? 'bg-indigo-100 text-indigo-800' : '' }}
                                                    {{ $appointment->status === 'completed' ? 'bg-green-100 text-green-800' : '' }}
                                                    {{ $appointment->status === 'closed' ? 'bg-gray-100 text-gray-800' : '' }}
                                                    {{ $appointment->status === 'cancelled' ? 'bg-red-100 text-red-800' : '' }}
                                                ">
                                                    {{ ucfirst($appointment->status) }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="mt-3 flex space-x-3">
                                            <a href="{{ route('doctor.appointments.show', $appointment->id) }}" 
                                               class="inline-flex items-center px-3 py-1 text-sm text-blue-600 hover:text-blue-800 bg-blue-50 hover:bg-blue-100 rounded-md transition-colors">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                                    <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                                                    <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" />
                                                </svg>
                                                View Details
                                            </a>
                                               
                                            @if($appointment->status === 'confirmed')
                                                <form method="POST" action="{{ route('doctor.appointments.start-consultation', $appointment->id) }}" class="inline-block">
                                                    @csrf
                                                    <button type="submit" class="inline-flex items-center px-3 py-1 text-sm text-indigo-600 hover:text-indigo-800 bg-indigo-50 hover:bg-indigo-100 rounded-md transition-colors">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd" />
                                                        </svg>
                                                        Start Consultation
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <div class="p-8 text-center">
                                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-blue-100 mb-4">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-blue-600" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <p class="text-gray-500">No appointments scheduled for today.</p>
                                    <p class="text-sm text-gray-400 mt-2">Check back later or view all appointments</p>
                                </div>
                            @endif
                        </div>
                        </div>
                        
                        {{-- Recent Patients --}}
                        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-indigo-50 to-purple-50 flex justify-between items-center">
                                <div class="flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-600 mr-2" viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z" />
                                    </svg>
                                    <h2 class="text-lg font-medium text-gray-800">Pending Actions</h2>
                                </div>
                                <div class="bg-indigo-100 text-indigo-800 text-xs px-2 py-1 rounded-full font-semibold">
                                    {{ $pendingActions->count() }} Actions
                                </div>
                            </div>
                            <div class="divide-y divide-gray-100">
                                @if($pendingActions->count() > 0)
                                    @foreach($pendingActions as $action)
                                        <div class="p-4 hover:bg-indigo-50/30 transition-colors duration-200">
                                            <div class="flex justify-between items-start">
                                                <div class="flex items-center">
                                                    <div class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 font-bold mr-3">
                                                        {{ substr($action->owner_name, 0, 1) }}
                                                    </div>
                                                    <div>
                                                        <h3 class="font-medium text-gray-900">{{ $action->owner_name }}</h3>
                                                        <div class="flex items-center text-sm text-gray-500 mt-1">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                                                <path d="M10 2a6 6 0 00-6 6v3.586l-.707.707A1 1 0 004 14h12a1 1 0 00.707-1.707L16 11.586V8a6 6 0 00-6-6zM10 18a3 3 0 01-3-3h6a3 3 0 01-3 3z" />
                                                            </svg>
                                                            @if($action->status === 'assigned')
                                                                <span class="text-purple-700">Needs confirmation</span>
                                                            @elseif($action->status === 'completed')
                                                                <span class="text-green-700">Needs consultation notes</span>
                                                            @endif
                                                        </div>
                                                        <div class="flex items-center text-xs text-gray-500 mt-1">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                                                <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" />
                                                            </svg>
                                                            {{ $action->appointment_date ? \Carbon\Carbon::parse($action->appointment_date)->format('M d, Y') : '' }}
                                                            {{ $action->appointment_time ? '- ' . \Carbon\Carbon::parse($action->appointment_time)->format('h:i A') : '' }}
                                                        </div>
                                                    </div>
                                                </div>
                                                <a href="{{ route('doctor.appointments.show', $action->id) }}" 
                                                   class="inline-flex items-center px-3 py-1 text-sm text-indigo-600 hover:text-indigo-800 bg-indigo-50 hover:bg-indigo-100 rounded-md transition-colors">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                                        <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                                                        <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" />
                                                    </svg>
                                                    View
                                                </a>
                                            </div>
                                        </div>
                                    @endforeach
                                @else
                                    <div class="p-8 text-center">
                                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-indigo-100 mb-4">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-indigo-600" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                        <p class="text-gray-500">No pending actions</p>
                                        <p class="text-sm text-gray-400 mt-2">You're all caught up!</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    
                    {{-- Upcoming Appointments --}}
                    <div class="mt-8 bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-cyan-50 to-blue-50 flex justify-between items-center">
                            <div class="flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-cyan-600 mr-2" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" />
                                </svg>
                                <h2 class="text-lg font-medium text-gray-800">Upcoming Appointments</h2>
                            </div>
                            <div class="bg-cyan-100 text-cyan-800 text-xs px-2 py-1 rounded-full font-semibold">
                                {{ $upcomingAppointments->count() }} Upcoming
                            </div>
                        </div>
                        <div class="p-4">
                            @if($upcomingAppointments->count() > 0)
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead>
                                            <tr>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Patient</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Schedule</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            @foreach($upcomingAppointments as $upcomingAppointment)
                                                <tr class="hover:bg-blue-50/30 transition-colors duration-200">
                                                    <td class="px-6 py-4">
                                                        <div class="flex items-center">
                                                            <div class="h-8 w-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-700 font-bold mr-3">
                                                                {{ substr($upcomingAppointment->owner_name, 0, 1) }}
                                                            </div>
                                                            <div>
                                                                <div class="font-medium text-gray-900">{{ $upcomingAppointment->owner_name }}</div>
                                                                <div class="text-sm text-gray-500">{{ $upcomingAppointment->owner_phone }}</div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="px-6 py-4">
                                                        <div class="text-sm text-gray-900 font-medium">
                                                            {{ $upcomingAppointment->appointment_date ? \Carbon\Carbon::parse($upcomingAppointment->appointment_date)->format('M d, Y') : 'No date set' }}
                                                        </div>
                                                        <div class="text-xs px-2 py-0.5 rounded bg-blue-50 text-blue-700 inline-block mt-1">
                                                            {{ $upcomingAppointment->appointment_time ? \Carbon\Carbon::parse($upcomingAppointment->appointment_time)->format('h:i A') : 'No time set' }}
                                                        </div>
                                                    </td>
                                                    <td class="px-6 py-4">
                                                        <span class="inline-flex px-2.5 py-1 text-xs font-semibold rounded-full
                                                            {{ $upcomingAppointment->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                                            {{ $upcomingAppointment->status === 'assigned' ? 'bg-purple-100 text-purple-800' : '' }}
                                                            {{ $upcomingAppointment->status === 'confirmed' ? 'bg-blue-100 text-blue-800' : '' }}
                                                            {{ $upcomingAppointment->status === 'in_progress' ? 'bg-indigo-100 text-indigo-800' : '' }}
                                                            {{ $upcomingAppointment->status === 'completed' ? 'bg-green-100 text-green-800' : '' }}
                                                            {{ $upcomingAppointment->status === 'closed' ? 'bg-gray-100 text-gray-800' : '' }}
                                                            {{ $upcomingAppointment->status === 'cancelled' ? 'bg-red-100 text-red-800' : '' }}
                                                        ">
                                                            {{ ucfirst($upcomingAppointment->status) }}
                                                        </span>
                                                    </td>
                                                    <td class="px-6 py-4 text-sm">
                                                        <a href="{{ route('doctor.appointments.show', $upcomingAppointment->id) }}" 
                                                           class="inline-flex items-center px-3 py-1 text-sm text-cyan-600 hover:text-cyan-800 bg-cyan-50 hover:bg-cyan-100 rounded-md transition-colors">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                                                <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                                                                <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" />
                                                            </svg>
                                                            View Details
                                                        </a>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="p-8 text-center">
                                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-cyan-100 mb-4">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-cyan-600" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <p class="text-gray-500">No upcoming appointments scheduled</p>
                                    <p class="text-sm text-gray-400 mt-2">Your upcoming appointments will appear here</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Doctor Role Indicator for JS -->
    <div class="hidden" data-role="doctor"></div>

    <!-- Global Notification System Script -->
    <script src="{{ asset('js/global-notifications.js') }}"></script>
</x-app-layout>
