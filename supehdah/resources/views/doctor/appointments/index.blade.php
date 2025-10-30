<x-app-layout>
    <!-- Doctor Notification System -->
    <div id="global-notification-container" class="fixed top-4 right-4 z-50 space-y-2" style="max-width: 350px;"></div>
    <div id="global-popup-notification-container" class="fixed bottom-4 right-4 z-50 space-y-2" style="max-width: 350px;"></div>
    <audio id="global-notification-sound" src="{{ asset('sounds/noti.mp3') }}" preload="auto"></audio>
    
    <div class="py-12 bg-gray-100 min-h-screen">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex space-x-6">
            
            {{-- Sidebar --}}
            <div class="w-1/4">
                @include('doctor.components.sidebar')
            </div>
            
            {{-- Main Content --}}
            <div class="flex-1">
                <div class="bg-white shadow-xl rounded-lg p-8">
                    <h1 class="text-2xl font-bold text-gray-800 mb-6">
                        @if(request()->get('status') == 'show_all')
                            All Appointments
                        @else
                            Active Appointments
                        @endif
                    </h1>
                    
                    @if(session('success'))
                        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
                            <p>{{ session('success') }}</p>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
                            <p>{{ session('error') }}</p>
                        </div>
                    @endif
                    
                    {{-- Filters --}}
                    <div class="mb-6 bg-gray-50 p-4 rounded-lg">
                        <form method="GET" action="{{ route('doctor.appointments.index') }}" class="flex items-center space-x-4">
                            <div class="flex-1">
                                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Filter by Status</label>
                                <select id="status" name="status" class="rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 w-full">
                                    <option value="">All Active</option>
                                    <option value="assigned" {{ request()->get('status') == 'assigned' ? 'selected' : '' }}>Assigned</option>
                                    <option value="confirmed" {{ request()->get('status') == 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                                    <option value="in_progress" {{ request()->get('status') == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                                    <option value="completed" {{ request()->get('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                                    <option value="show_all" {{ request()->get('status') == 'show_all' ? 'selected' : '' }}>Show All (Including Closed/Cancelled)</option>
                                </select>
                            </div>
                            
                            <div class="flex-1">
                                <label for="date" class="block text-sm font-medium text-gray-700 mb-1">Filter by Date</label>
                                <input type="date" id="date" name="date" value="{{ request()->get('date') }}" class="rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 w-full">
                            </div>
                            
                            <div class="flex-1 flex items-end">
                                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition duration-150">
                                    Apply Filters
                                </button>
                                
                                @if(request()->has('status') || request()->has('date'))
                                    <a href="{{ route('doctor.appointments.index') }}" class="ml-2 px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600 transition duration-150">
                                        Clear Filters
                                    </a>
                                @endif
                            </div>
                        </form>
                    </div>
                    
                    {{-- Appointments Table --}}
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Patient</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date & Time</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($appointments as $appointment)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="font-medium text-gray-900">{{ $appointment->owner_name }}</div>
                                            <div class="text-sm text-gray-500">{{ $appointment->owner_phone }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if($appointment->appointment_date)
                                                <div class="text-gray-900">
                                                    {{ \Carbon\Carbon::parse($appointment->appointment_date)->format('M d, Y') }}
                                                </div>
                                                <div class="text-sm text-blue-600">
                                                    {{ $appointment->appointment_time ? \Carbon\Carbon::parse($appointment->appointment_time)->format('h:i A') : 'No time set' }}
                                                </div>
                                            @else
                                                <span class="text-gray-500">Not scheduled</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                                {{ $appointment->status === 'assigned' ? 'bg-purple-100 text-purple-800' : '' }}
                                                {{ $appointment->status === 'confirmed' ? 'bg-blue-100 text-blue-800' : '' }}
                                                {{ $appointment->status === 'in_progress' ? 'bg-indigo-100 text-indigo-800' : '' }}
                                                {{ $appointment->status === 'completed' ? 'bg-green-100 text-green-800' : '' }}
                                                {{ $appointment->status === 'closed' ? 'bg-gray-100 text-gray-800' : '' }}
                                                {{ $appointment->status === 'cancelled' ? 'bg-red-100 text-red-800' : '' }}
                                            ">
                                                {{ ucfirst($appointment->status) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm space-x-3">
                                            <a href="{{ route('doctor.appointments.show', $appointment->id) }}" class="text-blue-600 hover:text-blue-900">View</a>
                                            
                                            @if($appointment->status === 'assigned')
                                                <div class="inline-block">
                                                    <form method="POST" action="{{ route('doctor.appointments.accept-decline', $appointment->id) }}" class="inline-flex">
                                                        @csrf
                                                        <input type="hidden" name="action" value="accept">
                                                        <button type="submit" class="text-green-600 hover:text-green-900">Accept</button>
                                                    </form>
                                                    <span class="text-gray-300 mx-1">|</span>
                                                    <form method="POST" action="{{ route('doctor.appointments.accept-decline', $appointment->id) }}" class="inline-flex">
                                                        @csrf
                                                        <input type="hidden" name="action" value="decline">
                                                        <button type="submit" class="text-red-600 hover:text-red-900">Decline</button>
                                                    </form>
                                                </div>
                                            @endif
                                            
                                            @if($appointment->status === 'confirmed')
                                                <form method="POST" action="{{ route('doctor.appointments.start-consultation', $appointment->id) }}" class="inline">
                                                    @csrf
                                                    <button type="submit" class="text-indigo-600 hover:text-indigo-900">Start Consultation</button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-6 py-4 text-center text-gray-500">No appointments found</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                    {{-- Pagination --}}
                    <div class="mt-4">
                        {{ $appointments->links() }}
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
