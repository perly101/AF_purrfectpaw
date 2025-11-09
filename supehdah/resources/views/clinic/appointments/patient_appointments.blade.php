@php
    use App\Models\ClinicInfo;
    $clinic = $clinic ?? ClinicInfo::where('user_id', auth()->id())->first();
@endphp

<x-app-layout>
    <div class="py-12 bg-gray-100 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-4">
                <a href="{{ route('clinic.appointments.archived') }}" class="text-blue-600 hover:underline flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Back to Patient List
                </a>
            </div>
                
            <div class="bg-white shadow-xl rounded-lg p-8">
                <div class="flex justify-between items-center mb-6">
                    <h1 class="text-2xl font-bold text-gray-800">
                        Appointment History for {{ $patientName }}
                    </h1>
                    <a href="{{ route('clinic.appointments.index') }}" 
                       class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition duration-150">
                        Active Appointments
                    </a>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gradient-to-r from-gray-700 to-gray-600">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Appointment Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Doctor</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Completion Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($appointments as $appointment)
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ \Carbon\Carbon::parse($appointment->appointment_date)->format('M d, Y') }}
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            {{ \Carbon\Carbon::parse($appointment->appointment_time)->format('h:i A') }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex flex-col space-y-1">
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full 
                                                {{ $appointment->status === 'completed' ? 'bg-green-100 text-green-800' : '' }}
                                                {{ $appointment->status === 'cancelled' ? 'bg-red-100 text-red-800' : '' }}">
                                                {{ ucfirst($appointment->status) }}
                                            </span>
                                            @if($appointment->status === 'completed')
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full
                                                    {{ ($appointment->payment_status ?? 'unpaid') === 'paid' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}
                                                ">
                                                    {{ ucfirst($appointment->payment_status ?? 'Unpaid') }}
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        @if($appointment->doctor)
                                            Dr. {{ $appointment->doctor->first_name }} {{ $appointment->doctor->last_name }}
                                        @else
                                            Not Assigned
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-500">
                                            {{ $appointment->updated_at->format('M d, Y') }}
                                            <div class="text-xs text-gray-400">
                                                {{ $appointment->updated_at->format('h:i A') }}
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                        <a href="{{ route('clinic.appointments.show', $appointment->id) }}" 
                                           class="text-blue-600 hover:text-blue-900">View Details</a>
                                        @if($appointment->status === 'completed' && ($appointment->payment_status ?? 'unpaid') === 'unpaid')
                                            <a href="{{ route('clinic.payments.create', $appointment->id) }}" 
                                               class="text-green-600 hover:text-green-900 ml-2">Process Payment</a>
                                        @endif
                                    </td>
                                </tr>
                                
                                @if($appointment->consultation_notes)
                                <tr class="bg-gray-50">
                                    <td colspan="5" class="px-6 py-4">
                                        <div class="mb-1 text-sm font-medium text-gray-700">Consultation Notes:</div>
                                        <div class="text-sm text-gray-600 whitespace-pre-wrap">{{ $appointment->consultation_notes }}</div>
                                    </td>
                                </tr>
                                @else
                                <tr class="bg-gray-50">
                                    <td colspan="5" class="px-6 py-2">
                                        <div class="text-sm text-gray-500 italic">No consultation notes available</div>
                                    </td>
                                </tr>
                                @endif
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                                        No appointment history found for this patient.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>