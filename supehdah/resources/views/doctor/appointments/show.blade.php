<x-app-layout>
    <!-- Doctor Notification System -->
    <div id="global-notification-container" class="fixed top-4 right-4 z-50 space-y-2" style="max-width: 350px;"></div>
    <div id="global-popup-notification-container" class="fixed bottom-4 right-4 z-50 space-y-2" style="max-width: 350px;"></div>
    <audio id="global-notification-sound" src="{{ asset('sounds/noti.mp3') }}" preload="auto"></audio>
    
    <div class="py-12 bg-gray-100 min-h-screen">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex space-x-6">
            
            {{-- Si                            </div>
                        </div>
                    </div>
                    
                    {{-- Display Cancellation Reason if appointment is cancelled --}}
                    @if($appointment->status === 'cancelled' && $appointment->notes)
                        <div class="bg-white border border-red-200 rounded-lg shadow-sm mb-6">
                            <div class="p-4 border-b bg-red-50">
                                <h2 class="text-lg font-medium text-red-800">Cancellation Information</h2>
                            </div>
                            <div class="p-4">
                                @php
                                    $cancellationData = json_decode($appointment->notes, true);
                                @endphp
                                
                                @if($cancellationData && isset($cancellationData['cancellation_reason']))
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Reason:</span>
                                        <span class="font-medium text-red-600">{{ $cancellationData['cancellation_reason'] }}</span>
                                    </div>
                                    
                                    @if(isset($cancellationData['declined_at']))
                                        <div class="flex justify-between mt-2">
                                            <span class="text-gray-600">Cancelled on:</span>
                                            <span class="text-sm text-gray-500">{{ \Carbon\Carbon::parse($cancellationData['declined_at'])->format('F d, Y h:i A') }}</span>
                                        </div>
                                    @endif
                                @else
                                    <p class="text-gray-500">No cancellation information available.</p>
                                @endif
                            </div>
                        </div>
                    @endif
                    
                    {{-- Consultation Notes Form --}} 
            <div class="w-1/4">
                @include('doctor.components.sidebar')
            </div>
            
            {{-- Main Content --}}
            <div class="flex-1">
                <div class="bg-white shadow-xl rounded-lg p-8">
                    <div class="flex justify-between items-center mb-6">
                        <h1 class="text-2xl font-bold text-gray-800">Appointment Details</h1>
                        <a href="{{ route('doctor.appointments.index') }}" 
                           class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 transition duration-150">
                            Back to List
                        </a>
                    </div>
                    
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
                    
                    {{-- Status Bar --}}
                    <div class="bg-gray-50 p-4 rounded-lg mb-6 flex justify-between items-center">
                        <div class="flex items-center space-x-4">
                            <span class="font-medium text-gray-700">Current Status:</span>
                            <span class="inline-flex px-3 py-1 text-sm font-semibold rounded-full
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
                        
                        <div class="flex space-x-3">
                            @if($appointment->status === 'assigned')
                                <form method="POST" action="{{ route('doctor.appointments.accept-decline', $appointment->id) }}">
                                    @csrf
                                    <input type="hidden" name="action" value="accept">
                                    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition duration-150">
                                        Accept Appointment
                                    </button>
                                </form>
                                
                                <form method="POST" action="{{ route('doctor.appointments.accept-decline', $appointment->id) }}">
                                    @csrf
                                    <input type="hidden" name="action" value="decline">
                                    <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition duration-150">
                                        Decline Appointment
                                    </button>
                                </form>
                            @endif
                            
                            @if($appointment->status === 'confirmed')
                                <form method="POST" action="{{ route('doctor.appointments.start-consultation', $appointment->id) }}">
                                    @csrf
                                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 transition duration-150">
                                        Start Consultation
                                    </button>
                                </form>
                            @endif
                            
                            @if($appointment->status === 'in_progress')
                                <form method="POST" action="{{ route('doctor.appointments.update-status', $appointment->id) }}">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="status" value="completed">
                                    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition duration-150">
                                        Complete Consultation
                                    </button>
                                </form>
                            @endif

                            {{-- Manual SMS Button - Available for any appointment with phone --}}
                            @if($appointment->owner_phone)
                                <form method="POST" action="{{ route('doctor.appointments.send-sms', $appointment->id) }}" class="inline-block">
                                    @csrf
                                    <button type="submit" 
                                            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition duration-150 flex items-center space-x-2"
                                            onclick="return confirm('Send SMS notification to {{ $appointment->owner_phone }}?')">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                                        </svg>
                                        <span>Send SMS Notification</span>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                    
                    {{-- Patient & Appointment Info --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        {{-- Patient Info --}}
                        <div class="bg-white border rounded-lg shadow-sm">
                            <div class="p-4 border-b bg-gray-50">
                                <h2 class="text-lg font-medium text-gray-800">Patient Information</h2>
                            </div>
                            <div class="p-4 space-y-3">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Name:</span>
                                    <span class="font-medium">{{ $appointment->owner_name }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Phone:</span>
                                    <span class="font-medium">{{ $appointment->owner_phone }}</span>
                                </div>
                                <div class="border-t pt-3 mt-3">
                                    <h3 class="text-sm font-medium text-gray-700 mb-2">Previous Visits</h3>
                                    @if(count($patientHistory) > 0)
                                        <ul class="text-sm text-gray-600">
                                            @foreach($patientHistory as $history)
                                                <li class="mb-1">
                                                    <a href="{{ route('doctor.appointments.show', $history->id) }}" class="text-blue-600 hover:underline">
                                                        {{ \Carbon\Carbon::parse($history->appointment_date)->format('M d, Y') }} - 
                                                        {{ ucfirst($history->status) }}
                                                    </a>
                                                </li>
                                            @endforeach
                                        </ul>
                                    @else
                                        <p class="text-sm text-gray-500">No previous visits</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                        
                        {{-- Appointment Info --}}
                        <div class="bg-white border rounded-lg shadow-sm">
                            <div class="p-4 border-b bg-gray-50">
                                <h2 class="text-lg font-medium text-gray-800">Appointment Details</h2>
                            </div>
                            <div class="p-4 space-y-3">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Appointment ID:</span>
                                    <span class="font-medium">#{{ $appointment->id }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Date:</span>
                                    <span class="font-medium">{{ $appointment->appointment_date ? \Carbon\Carbon::parse($appointment->appointment_date)->format('F d, Y') : 'Not scheduled' }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Time:</span>
                                    <span class="font-medium">{{ $appointment->appointment_time ? \Carbon\Carbon::parse($appointment->appointment_time)->format('h:i A') : 'Not scheduled' }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Created:</span>
                                    <span class="font-medium">{{ $appointment->created_at->format('F d, Y h:i A') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Custom Fields --}}
                    @if($appointment->customValues && count($appointment->customValues) > 0)
                        <div class="bg-white border rounded-lg shadow-sm mb-6">
                            <div class="p-4 border-b bg-gray-50">
                                <h2 class="text-lg font-medium text-gray-800">Additional Information</h2>
                            </div>
                            <div class="p-4">
                                <table class="min-w-full">
                                    <tbody>
                                        @foreach($appointment->customValues as $value)
                                            <tr class="border-b last:border-b-0">
                                                <td class="py-3 px-4 text-sm font-medium text-gray-900">{{ $value->field->label }}</td>
                                                <td class="py-3 px-4 text-sm text-gray-500">
                                                    @if(is_array($value->value))
                                                        {{ implode(', ', $value->value) }}
                                                    @elseif(is_bool($value->value))
                                                        {{ $value->value ? 'Yes' : 'No' }}
                                                    @else
                                                        {{ $value->value }}
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                    
                    {{-- Consultation Notes Form --}}
                    @if($appointment->status === 'completed')
                        <div class="bg-white border rounded-lg shadow-sm mb-6">
                            <div class="p-4 border-b bg-gray-50">
                                <h2 class="text-lg font-medium text-gray-800">Consultation Notes</h2>
                                <p class="text-sm text-gray-500">Please complete the consultation notes to close this appointment</p>
                            </div>
                            <div class="p-4">
                                <form method="POST" action="{{ route('doctor.appointments.complete-consultation', $appointment->id) }}">
                                    @csrf
                                    
                                    <div class="mb-4">
                                        <label for="chief_complaint" class="block text-sm font-medium text-gray-700 mb-1">Chief Complaint / Reason for Visit</label>
                                        <textarea id="chief_complaint" name="chief_complaint" rows="2" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">{{ old('chief_complaint') }}</textarea>
                                        @error('chief_complaint')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    
                                    <div class="mb-4">
                                        <label for="history_observations" class="block text-sm font-medium text-gray-700 mb-1">History / Observations</label>
                                        <textarea id="history_observations" name="history_observations" rows="3" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">{{ old('history_observations') }}</textarea>
                                        @error('history_observations')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    
                                    <div class="mb-4">
                                        <label for="examination_findings" class="block text-sm font-medium text-gray-700 mb-1">Examination Findings</label>
                                        <textarea id="examination_findings" name="examination_findings" rows="3" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">{{ old('examination_findings') }}</textarea>
                                        @error('examination_findings')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    
                                    <div class="mb-4">
                                        <label for="diagnosis" class="block text-sm font-medium text-gray-700 mb-1">Diagnosis</label>
                                        <textarea id="diagnosis" name="diagnosis" rows="2" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">{{ old('diagnosis') }}</textarea>
                                        @error('diagnosis')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    
                                    <div class="mb-4">
                                        <label for="plan_recommendations" class="block text-sm font-medium text-gray-700 mb-1">Plan & Recommendations</label>
                                        <textarea id="plan_recommendations" name="plan_recommendations" rows="3" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">{{ old('plan_recommendations') }}</textarea>
                                        @error('plan_recommendations')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    
                                    <div class="flex justify-end">
                                        <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition duration-150">
                                            Save Notes & Close Appointment
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    @endif
                    
                    {{-- Display Consultation Notes if available --}}
                    @if($appointment->notes && $appointment->status === 'closed')
                        <div class="bg-white border rounded-lg shadow-sm mb-6">
                            <div class="p-4 border-b bg-gray-50">
                                <h2 class="text-lg font-medium text-gray-800">Consultation Notes</h2>
                            </div>
                            <div class="p-4">
                                @php
                                    $notes = json_decode($appointment->notes, true);
                                @endphp
                                
                                @if($notes)
                                    <div class="space-y-4">
                                        <div>
                                            <h3 class="text-sm font-medium text-gray-700">Chief Complaint / Reason for Visit</h3>
                                            <p class="mt-1 p-2 bg-gray-50 rounded">{{ $notes['chief_complaint'] ?? 'N/A' }}</p>
                                        </div>
                                        
                                        <div>
                                            <h3 class="text-sm font-medium text-gray-700">History / Observations</h3>
                                            <p class="mt-1 p-2 bg-gray-50 rounded">{{ $notes['history_observations'] ?? 'N/A' }}</p>
                                        </div>
                                        
                                        <div>
                                            <h3 class="text-sm font-medium text-gray-700">Examination Findings</h3>
                                            <p class="mt-1 p-2 bg-gray-50 rounded">{{ $notes['examination_findings'] ?? 'N/A' }}</p>
                                        </div>
                                        
                                        <div>
                                            <h3 class="text-sm font-medium text-gray-700">Diagnosis</h3>
                                            <p class="mt-1 p-2 bg-gray-50 rounded">{{ $notes['diagnosis'] ?? 'N/A' }}</p>
                                        </div>
                                        
                                        <div>
                                            <h3 class="text-sm font-medium text-gray-700">Plan & Recommendations</h3>
                                            <p class="mt-1 p-2 bg-gray-50 rounded">{{ $notes['plan_recommendations'] ?? 'N/A' }}</p>
                                        </div>
                                        
                                        @if(isset($notes['completed_at']))
                                            <div class="text-xs text-gray-500 text-right">
                                                Completed on: {{ \Carbon\Carbon::parse($notes['completed_at'])->format('F d, Y h:i A') }}
                                            </div>
                                        @endif
                                    </div>
                                @else
                                    <p class="text-gray-500">Consultation notes are not properly formatted.</p>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Doctor Role Indicator for JS -->
    <div class="hidden" data-role="doctor"></div>

    <!-- Global Notification System Script -->
    <script src="{{ asset('js/global-notifications.js') }}"></script>
</x-app-layout>
