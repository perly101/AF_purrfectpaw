@php
    use App\Models\ClinicInfo;
    $clinic = $clinic ?? ClinicInfo::where('user_id', auth()->id())->first();
@endphp

<x-app-layout>
    <div class="py-6 bg-gray-100 min-h-screen">
        <div class="px-4 sm:px-6 lg:px-8 flex">
            
            {{-- Sidebar --}}
            <div class="w-64 flex-shrink-0 mr-6">
                @include('clinic.components.sidebar')
            </div>
            
            <div class="flex-1">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6">
                    <div class="mb-4 md:mb-0">
                        <h2 class="text-xl md:text-2xl font-semibold text-gray-800">Appointment Management</h2>
                        <p class="text-gray-500 text-sm mt-1">View and manage all clinic appointments</p>
                        <div class="mt-2 flex space-x-2">
                            <div class="text-xs bg-orange-100 text-orange-800 px-2 py-1 rounded-full inline-flex items-center">
                                <i class="fas fa-calendar-day mr-1"></i>
                                Same-Day Booking Policy Active
                            </div>
                            <div class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded-full inline-flex items-center">
                                <i class="fas fa-clock mr-1"></i>
                                Timezone: Asia/Manila (UTC+8)
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex space-x-2">
                        <a href="{{ route('clinic.appointments.archived') }}" 
                           class="bg-gray-700 hover:bg-gray-800 text-white px-4 py-2 rounded-lg text-sm flex items-center transition">
                            <i class="fas fa-archive mr-2"></i> View Archived Appointments
                        </a>
                    </div>
                </div>

                <div class="bg-white shadow-lg border border-gray-200 rounded-lg p-6">
                    
                    @if(session('success'))
                        <div class="bg-green-100 border border-green-300 rounded-lg shadow-sm text-green-700 p-4 mb-6 flex items-center" role="alert">
                            <i class="fas fa-check-circle text-green-500 mr-2"></i>
                            <p>{{ session('success') }}</p>
                        </div>
                    @endif

                    {{-- Payment Notice --}}
                    @php
                        $unpaidCompletedCount = $appointments->where('status', 'completed')->whereIn('payment_status', [null, 'unpaid'])->count();
                    @endphp
                    @if($unpaidCompletedCount > 0)
                        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-yellow-700">
                                        <strong>{{ $unpaidCompletedCount }} completed appointment{{ $unpaidCompletedCount > 1 ? 's' : '' }} require{{ $unpaidCompletedCount === 1 ? 's' : '' }} payment processing.</strong>
                                        Look for the <span class="font-semibold">💳 Process Payment</span> button to process payments at the counter.
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endif
                    
                    <div class="overflow-x-auto border border-gray-200 rounded-lg shadow">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr class="bg-gray-50">
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Owner</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Phone</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Appointment Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($appointments as $appointment)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            #{{ $appointment->id }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $appointment->owner_name }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $appointment->owner_phone }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex flex-col space-y-1">
                                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                                    {{ $appointment->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                                    {{ $appointment->status === 'assigned' ? 'bg-purple-100 text-purple-800' : '' }}
                                                    {{ $appointment->status === 'confirmed' ? 'bg-blue-100 text-blue-800' : '' }}
                                                    {{ $appointment->status === 'completed' ? 'bg-green-100 text-green-800' : '' }}
                                                    {{ $appointment->status === 'closed' ? 'bg-gray-100 text-gray-800' : '' }}
                                                    {{ $appointment->status === 'cancelled' ? 'bg-red-100 text-red-800' : '' }}
                                                ">
                                                    {{ ucfirst($appointment->status) }}
                                                </span>
                                                @if($appointment->status === 'completed')
                                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                                        {{ ($appointment->payment_status ?? 'unpaid') === 'paid' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}
                                                    ">
                                                        {{ ucfirst($appointment->payment_status ?? 'Unpaid') }}
                                                    </span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if($appointment->appointment_date)
                                                <div class="text-sm text-gray-700 font-medium">
                                                    {{ \Carbon\Carbon::parse($appointment->appointment_date)->setTimezone('Asia/Manila')->format('M d, Y') }}
                                                </div>
                                                <div class="text-xs bg-blue-100 text-blue-800 inline-flex rounded-full px-2 py-1 mt-1">
                                                    @if($appointment->appointment_time)
                                                        @php
                                                            // Handle proper date/time parsing - assume stored time is in Manila timezone
                                                            $date = \Carbon\Carbon::parse($appointment->appointment_date)->format('Y-m-d');
                                                            $datetime = $date . ' ' . $appointment->appointment_time;
                                                        @endphp
                                                        {{ \Carbon\Carbon::parse($datetime, 'Asia/Manila')->format('h:i A') }}
                                                    @else
                                                        No time set
                                                    @endif
                                                </div>
                                                <div class="text-xs text-gray-500 mt-1">
                                                    <i class="fas fa-clock mr-1"></i>Asia/Manila (UTC+8)
                                                </div>
                                            @else
                                                <span class="text-sm text-gray-500">
                                                    {{ $appointment->created_at->setTimezone('Asia/Manila')->format('M d, Y h:i A') }}
                                                </span>
                                                <div class="text-xs text-gray-400">
                                                    <i class="fas fa-info-circle mr-1"></i>Created (UTC+8)
                                                </div>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <div class="flex flex-col space-y-2">
                                                <div class="flex space-x-3">
                                                    <a href="{{ route('clinic.appointments.show', $appointment->id) }}" 
                                                       class="text-blue-600 hover:text-blue-900">View</a>
                                                    
                                                    @if($appointment->status === 'pending')
                                                        <button 
                                                            type="button" 
                                                            onclick="openAssignModal({{ $appointment->id }})"
                                                            class="text-purple-600 hover:text-purple-900">
                                                            Assign Doctor
                                                        </button>
                                                    @endif

                                                    @if($appointment->status === 'completed' && ($appointment->payment_status ?? 'unpaid') === 'unpaid')
                                                        <a href="{{ route('clinic.payments.create', $appointment->id) }}" 
                                                           class="text-green-600 hover:text-green-900 font-medium">
                                                            💳 Process Payment
                                                        </a>
                                                    @endif
                                                    
                                                    <form action="{{ route('clinic.appointments.delete', $appointment->id) }}" 
                                                          method="POST" 
                                                          onsubmit="return confirm('Are you sure you want to delete this appointment? This will make the slot available for booking again.')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="text-red-600 hover:text-red-900">
                                                            Delete
                                                        </button>
                                                    </form>
                                                </div>
                                                
                                                @if($appointment->doctor_id)
                                                    <div class="text-xs text-green-600">
                                                        👨‍⚕️ Dr. {{ $appointment->doctor->first_name }} {{ $appointment->doctor->last_name }}
                                                    </div>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-6 py-10 text-center">
                                            <div class="flex flex-col items-center justify-center">
                                                <i class="fas fa-calendar-times text-4xl text-gray-300 mb-3"></i>
                                                <p class="text-gray-500 font-medium">No appointments found</p>
                                                <p class="text-gray-400 text-sm">Appointments will appear here when scheduled</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-6 bg-white px-4 py-3 border-t border-gray-200 flex items-center justify-between">
                        {{ $appointments->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Assign Doctor Modal -->
    <div id="assignDoctorModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 items-center justify-center z-50 hidden" style="display: none;">
        <div class="bg-white rounded-lg shadow-xl border border-gray-200 p-6 w-full max-w-md mx-auto mt-20">
            <div class="flex justify-between items-center mb-4 border-b border-gray-200 pb-3">
                <h3 class="text-lg font-bold text-gray-900">Assign Doctor to Appointment</h3>
                <button onclick="closeAssignModal()" class="text-gray-500 hover:text-gray-700 focus:outline-none">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form id="assignDoctorForm" method="POST" action="">
                @csrf
                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-medium mb-2" for="doctor_id">
                        <i class="fas fa-user-md mr-1"></i> Select Doctor
                    </label>
                    <div class="relative">
                        <select id="doctor_id" name="doctor_id" class="block w-full p-3 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 shadow-sm appearance-none bg-white">
                            <option value="">-- Select a doctor --</option>
                            @foreach(App\Models\Doctor::where('clinic_id', $clinic->id)->where('availability_status', 'active')->get() as $doctor)
                                <option value="{{ $doctor->id }}">{{ $doctor->first_name }} {{ $doctor->last_name }} ({{ $doctor->specialization }})</option>
                            @endforeach
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                            <i class="fas fa-chevron-down"></i>
                        </div>
                    </div>
                </div>
                <div class="flex justify-end pt-3 border-t border-gray-200 mt-6">
                    <button type="button" onclick="closeAssignModal()" class="bg-gray-500 hover:bg-gray-600 text-white font-medium py-2 px-5 rounded-md mr-3">
                        <i class="fas fa-times mr-1"></i> Cancel
                    </button>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-5 rounded-md">
                        <i class="fas fa-check mr-1"></i> Assign
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openAssignModal(appointmentId) {
            document.getElementById('assignDoctorForm').action = "{{ url('/clinic/appointments') }}/" + appointmentId + "/assign-doctor";
            const modal = document.getElementById('assignDoctorModal');
            modal.classList.remove('hidden');
            modal.style.display = 'flex';
        }

        function closeAssignModal() {
            const modal = document.getElementById('assignDoctorModal');
            modal.classList.add('hidden');
            modal.style.display = 'none';
        }
    </script>
</x-app-layout>
