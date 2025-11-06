{{-- resources/views/clinic/payment.blade.php --}}

<x-app-layout>
    <div class="min-h-screen bg-gray-100 py-6 flex flex-col justify-center">
        <div class="sm:max-w-xl md:max-w-2xl mx-auto">
            <div class="mb-6 text-center">
                <div class="inline-flex items-center justify-center h-16 w-16 rounded-full bg-green-100 mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                    </svg>
                </div>
                <h2 class="text-2xl md:text-3xl font-bold text-gray-800">Complete Your Payment</h2>
                <p class="text-gray-500 text-sm md:text-base mt-2">One last step to activate your clinic on PurrfectPaw</p>
            </div>

            <div class="bg-white shadow-lg rounded-xl p-4 sm:p-6 md:p-8 border border-gray-200">
                <!-- Order Summary -->
                <div class="mb-6">
                    <h3 class="text-lg font-medium text-gray-800 mb-3 pb-2 border-b">Order Summary</h3>
                    
                    <div class="flex justify-between py-2 border-b">
                        <span class="text-gray-600">Clinic Name:</span>
                        <span class="font-medium text-gray-800">{{ $subscription->clinic->clinic_name }}</span>
                    </div>
                    <div class="flex justify-between py-2 border-b">
                        <span class="text-gray-600">Plan:</span>
                        <span class="font-medium text-gray-800">{{ ucfirst($subscription->plan_type) }} Plan</span>
                    </div>
                    <div class="flex justify-between py-2 border-b">
                        <span class="text-gray-600">Amount:</span>
                        <span class="font-medium text-gray-800">₱{{ number_format($subscription->amount, 2) }}</span>
                    </div>
                    <div class="flex justify-between py-2 text-lg">
                        <span class="font-medium text-gray-700">Total:</span>
                        <span class="font-bold text-indigo-600">₱{{ number_format($subscription->amount, 2) }}</span>
                    </div>
                </div>

                <!-- GCash Payment Section -->
                <div class="mb-6 p-4 border border-gray-200 rounded-lg bg-gray-50">
                    <div class="flex items-center mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span class="text-gray-800 font-medium">Pay with GCash</span>
                    </div>
                    
                    <div class="flex flex-col md:flex-row items-center justify-center space-y-4 md:space-y-0 md:space-x-6">
                        <!-- Real GCash QR Code -->
                        <div class="bg-white p-4 rounded-lg border border-gray-300 flex items-center justify-center">
                            <div class="text-center">
                                <img src="{{ asset('images/qr1.jpg') }}" alt="GCash QR Code" class="w-48 h-48 object-contain">
                                <p class="text-blue-600 font-medium text-sm mt-2">PurrfectPaw Veterinary</p>
                            </div>
                        </div>
                        
                        <div class="max-w-xs text-center md:text-left">
                            <h4 class="font-medium text-gray-800 mb-1">How to Pay:</h4>
                            <ol class="text-sm text-gray-600 space-y-1 list-decimal list-inside">
                                <li>Open your GCash app</li>
                                <li>Tap on "Pay QR"</li>
                                <li>Scan the QR code or save this image</li>
                                <li>Enter ₱{{ number_format($subscription->amount, 2) }} exactly</li>
                                <li>Add a reference number (your clinic name)</li>
                                <li>Confirm payment</li>
                            </ol>
                            <p class="text-xs text-gray-500 mt-2">After payment, click the button below to activate your account.</p>
                        </div>
                    </div>
                </div>
                
                <!-- Payment Confirmation Section -->
                <div class="text-center">
                    <p class="mb-4 text-gray-500 text-sm">
                        <span class="bg-blue-100 text-blue-800 text-xs font-medium mr-2 px-2.5 py-0.5 rounded">Important</span>
                        After sending payment through GCash, please enter your reference number and confirm below
                    </p>
                    
                    <form action="{{ route('payment.process') }}" method="POST" class="max-w-md mx-auto">
                        @csrf
                        <input type="hidden" name="subscription_id" value="{{ $subscription->id }}">
                        
                        <div class="mb-4">
                            <label for="reference_number" class="block text-sm font-medium text-gray-700 text-left mb-1">GCash Reference Number <span class="text-red-500">*</span></label>
                            <input type="text" id="reference_number" name="reference_number" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                                placeholder="Enter 9-digit GCash reference number"
                                maxlength="9"
                                pattern="[0-9]{9}"
                                title="Please enter exactly 9 digits"
                                oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0,9)">
                            <p class="mt-1 text-xs text-gray-500 text-left">Enter exactly 9 digits from your GCash transaction reference number.</p>
                            
                            @error('reference_number')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <button type="submit" class="px-6 py-3 rounded-lg bg-green-600 text-white font-medium shadow-md hover:bg-green-700 focus:ring-2 focus:ring-green-400 transition flex items-center justify-center mx-auto">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            I've Made the Payment
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const referenceInput = document.getElementById('reference_number');
            const submitButton = document.querySelector('button[type="submit"]');
            const form = document.querySelector('form');

            // Real-time validation feedback
            referenceInput.addEventListener('input', function() {
                const value = this.value;
                const isValid = /^[0-9]{9}$/.test(value);
                
                // Update input styling based on validity
                if (value.length === 0) {
                    // Empty state
                    this.classList.remove('border-green-500', 'border-red-500');
                    this.classList.add('border-gray-300');
                } else if (isValid) {
                    // Valid 9 digits
                    this.classList.remove('border-red-500', 'border-gray-300');
                    this.classList.add('border-green-500');
                } else {
                    // Invalid (wrong length or non-numeric)
                    this.classList.remove('border-green-500', 'border-gray-300');
                    this.classList.add('border-red-500');
                }
                
                // Update submit button state
                if (isValid) {
                    submitButton.disabled = false;
                    submitButton.classList.remove('opacity-50', 'cursor-not-allowed');
                } else {
                    submitButton.disabled = true;
                    submitButton.classList.add('opacity-50', 'cursor-not-allowed');
                }
            });

            // Form submission validation
            form.addEventListener('submit', function(e) {
                const referenceValue = referenceInput.value;
                
                if (!/^[0-9]{9}$/.test(referenceValue)) {
                    e.preventDefault();
                    alert('Please enter exactly 9 digits for the GCash reference number.');
                    referenceInput.focus();
                    return false;
                }
            });

            // Initialize button state
            submitButton.disabled = true;
            submitButton.classList.add('opacity-50', 'cursor-not-allowed');
        });
    </script>
</x-app-layout>