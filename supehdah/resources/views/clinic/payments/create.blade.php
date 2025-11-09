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
            
            <div class="flex-1 max-w-3xl">
                <div class="mb-6">
                    <h2 class="text-2xl font-semibold text-gray-800">Process Payment</h2>
                    <p class="text-gray-500 text-sm mt-1">Process payment for completed appointment at counter</p>
                </div>

                @if(session('error'))
                    <div class="bg-red-100 border border-red-300 rounded-lg text-red-700 p-4 mb-6" role="alert">
                        {{ session('error') }}
                    </div>
                @endif

                <div class="bg-white shadow-lg border border-gray-200 rounded-lg overflow-hidden">
                    {{-- Appointment Details Header --}}
                    <div class="bg-blue-50 px-6 py-4 border-b">
                        <div class="flex justify-between items-start">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900">Appointment #{{ $appointment->id }}</h3>
                                <div class="mt-2 space-y-1">
                                    <p class="text-sm text-gray-600">
                                        <span class="font-medium">Patient:</span> {{ $appointment->owner_name }}
                                    </p>
                                    <p class="text-sm text-gray-600">
                                        <span class="font-medium">Phone:</span> {{ $appointment->owner_phone }}
                                    </p>
                                    @if($appointment->doctor)
                                    <p class="text-sm text-gray-600">
                                        <span class="font-medium">Doctor:</span> Dr. {{ $appointment->doctor->first_name }} {{ $appointment->doctor->last_name }}
                                    </p>
                                    @endif
                                    <p class="text-sm text-gray-600">
                                        <span class="font-medium">Date:</span> {{ $appointment->appointment_date ? \Carbon\Carbon::parse($appointment->appointment_date)->format('M d, Y') : 'Not set' }}
                                        @if($appointment->appointment_time)
                                            at {{ \Carbon\Carbon::parse($appointment->appointment_time)->format('h:i A') }}
                                        @endif
                                    </p>
                                    @if($appointment->customValues && $appointment->customValues->count() > 0)
                                        <div class="mt-3">
                                            <p class="text-sm font-medium text-gray-600 mb-1">Appointment Details:</p>
                                            <div class="space-y-1">
                                                @foreach($appointment->customValues as $value)
                                                    <p class="text-xs text-gray-500">
                                                        <span class="font-medium">{{ $value->field->label }}:</span>
                                                        @if(is_array($value->value))
                                                            {{ implode(', ', $value->value) }}
                                                        @else
                                                            {{ $value->value ?: 'Not provided' }}
                                                        @endif
                                                    </p>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="inline-flex px-3 py-1 text-sm font-semibold rounded-full bg-green-100 text-green-800">
                                    {{ ucfirst($appointment->status) }}
                                </span>
                                <div class="mt-2">
                                    <span class="inline-flex px-3 py-1 text-sm font-semibold rounded-full bg-red-100 text-red-800">
                                        Unpaid
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Payment Form --}}
                    <form method="POST" action="{{ route('clinic.payments.store', $appointment->id) }}" class="p-6">
                        @csrf
                        
                        <div class="space-y-6">
                            {{-- Service Description --}}
                            <div>
                                <label for="service_description" class="block text-sm font-medium text-gray-700 mb-2">
                                    Service Description *
                                </label>
                                
                                @if($clinicFields->count() > 0)
                                    {{-- Show dropdown with clinic's custom fields and common services --}}
                                    <select id="service_description" 
                                            name="service_description" 
                                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                            required>
                                        <option value="">Select service type</option>
                                        
                                        {{-- Common veterinary services --}}
                                        <optgroup label="Common Services">
                                            <option value="Veterinary Consultation" {{ old('service_description') === 'Veterinary Consultation' ? 'selected' : '' }}>
                                                Veterinary Consultation
                                            </option>
                                            <option value="General Check-up" {{ old('service_description') === 'General Check-up' ? 'selected' : '' }}>
                                                General Check-up
                                            </option>
                                            <option value="Vaccination" {{ old('service_description') === 'Vaccination' ? 'selected' : '' }}>
                                                Vaccination
                                            </option>
                                            <option value="Health Certificate" {{ old('service_description') === 'Health Certificate' ? 'selected' : '' }}>
                                                Health Certificate
                                            </option>
                                            <option value="Deworming" {{ old('service_description') === 'Deworming' ? 'selected' : '' }}>
                                                Deworming
                                            </option>
                                            <option value="Grooming Service" {{ old('service_description') === 'Grooming Service' ? 'selected' : '' }}>
                                                Grooming Service
                                            </option>
                                            <option value="Emergency Treatment" {{ old('service_description') === 'Emergency Treatment' ? 'selected' : '' }}>
                                                Emergency Treatment
                                            </option>
                                        </optgroup>
                                        
                                        @if($clinicFields->where('type', 'select')->count() > 0)
                                        {{-- Services from appointment form fields --}}
                                        <optgroup label="From Appointment Form">
                                            @foreach($clinicFields as $field)
                                                @if($field->type === 'select' && $field->options)
                                                    @foreach($field->options as $option)
                                                        <option value="{{ $field->label }}: {{ $option }}" 
                                                                {{ old('service_description') === $field->label . ': ' . $option ? 'selected' : '' }}>
                                                            {{ $field->label }}: {{ $option }}
                                                        </option>
                                                    @endforeach
                                                @endif
                                            @endforeach
                                        </optgroup>
                                        @endif
                                        
                                        <option value="Other Service" {{ old('service_description') === 'Other Service' ? 'selected' : '' }}>
                                            Other Service
                                        </option>
                                    </select>
                                    
                                    {{-- Show text input if "Other Service" is selected --}}
                                    <input type="text" 
                                           id="custom_service_description" 
                                           name="custom_service_description" 
                                           value="{{ old('custom_service_description') }}"
                                           class="w-full mt-2 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 hidden"
                                           placeholder="Enter custom service description">
                                @else
                                    {{-- Fallback to text input if no fields configured --}}
                                    <input type="text" 
                                           id="service_description" 
                                           name="service_description" 
                                           value="{{ old('service_description', 'Veterinary Consultation') }}"
                                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                           placeholder="e.g., Veterinary Consultation, Check-up, Vaccination"
                                           required>
                                @endif
                                
                                @error('service_description')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Amount --}}
                            <div>
                                <label for="amount" class="block text-sm font-medium text-gray-700 mb-2">
                                    Amount (PHP) *
                                </label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">₱</span>
                                    <input type="number" 
                                           id="amount" 
                                           name="amount" 
                                           value="{{ old('amount') }}"
                                           step="0.01" 
                                           min="0.01"
                                           class="w-full pl-8 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                           placeholder="0.00"
                                           required>
                                </div>
                                @error('amount')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Payment Method --}}
                            <div>
                                <label for="payment_method" class="block text-sm font-medium text-gray-700 mb-2">
                                    Payment Method *
                                </label>
                                <select id="payment_method" 
                                        name="payment_method" 
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                        required>
                                    <option value="">Select payment method</option>
                                    <option value="cash" {{ old('payment_method') === 'cash' ? 'selected' : '' }}>Cash</option>
                                    <option value="credit_card" {{ old('payment_method') === 'credit_card' ? 'selected' : '' }}>Credit Card</option>
                                    <option value="debit_card" {{ old('payment_method') === 'debit_card' ? 'selected' : '' }}>Debit Card</option>
                                    <option value="gcash" {{ old('payment_method') === 'gcash' ? 'selected' : '' }}>GCash</option>
                                    <option value="paymaya" {{ old('payment_method') === 'paymaya' ? 'selected' : '' }}>PayMaya</option>
                                </select>
                                @error('payment_method')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Payment Notes --}}
                            <div>
                                <label for="payment_notes" class="block text-sm font-medium text-gray-700 mb-2">
                                    Notes (Optional)
                                </label>
                                <textarea id="payment_notes" 
                                          name="payment_notes" 
                                          rows="3"
                                          class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                          placeholder="Any additional notes about the payment...">{{ old('payment_notes') }}</textarea>
                                @error('payment_notes')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        {{-- Form Actions --}}
                        <div class="flex justify-between items-center mt-8 pt-6 border-t">
                            <a href="{{ route('clinic.appointments.show', $appointment->id) }}" 
                               class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition">
                                Cancel
                            </a>
                            
                            <button type="submit" 
                                    class="px-6 py-2 bg-green-600 hover:bg-green-700 text-white rounded-md font-medium transition">
                                Process Payment & Generate Receipt
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- JavaScript for handling service description dropdown --}}
    @if($clinicFields->count() > 0)
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const serviceSelect = document.getElementById('service_description');
            const customInput = document.getElementById('custom_service_description');
            
            if (serviceSelect && customInput) {
                serviceSelect.addEventListener('change', function() {
                    if (this.value === 'Other Service') {
                        customInput.classList.remove('hidden');
                        customInput.required = true;
                        // Clear the select value so custom input is used
                        this.name = 'service_description_backup';
                        customInput.name = 'service_description';
                    } else {
                        customInput.classList.add('hidden');
                        customInput.required = false;
                        customInput.value = '';
                        // Restore the select name
                        this.name = 'service_description';
                        customInput.name = 'custom_service_description';
                    }
                });
                
                // Check initial state on page load
                if (serviceSelect.value === 'Other Service') {
                    customInput.classList.remove('hidden');
                    customInput.required = true;
                    serviceSelect.name = 'service_description_backup';
                    customInput.name = 'service_description';
                }
            }
        });
    </script>
    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>