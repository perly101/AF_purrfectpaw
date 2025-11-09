<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Payment Receipt - {{ $receipt->receipt_number ?? 'RCPT-2025-00007' }}</title>
    
    {{-- Tailwind CSS CDN --}}
    <script src="https://cdn.tailwindcss.com"></script>
    
    {{-- Print Styles --}}
    <style>
        @media print {
            body { margin: 0; padding: 0; }
            .print\\:hidden { display: none !important; }
            .print\\:shadow-none { box-shadow: none !important; }
            .print\\:border-none { border: none !important; }
            .max-w-4xl { max-width: 100% !important; }
            .shadow-xl { box-shadow: none !important; }
            .rounded-lg { border-radius: 0 !important; }
            .border { border: none !important; }
        }
        
        @page { 
            margin: 0.5in; 
            size: A4; 
        }
        
        .print-area {
            page-break-inside: avoid;
        }
        
        .bg-gradient-to-r {
            background: #2563eb !important;
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen print:bg-white">
    
    {{-- Main Container --}}
    <div class="container mx-auto px-4 py-6 max-w-4xl print:max-w-none print:px-0 print:py-0">
        
        {{-- Print Button Bar (Hidden in Print) --}}
        <div class="mb-6 flex justify-between items-center print:hidden bg-white rounded-lg shadow p-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Payment Receipt</h1>
                <p class="text-gray-600">Receipt #{{ $receipt->receipt_number ?? 'RCPT-2025-00007' }}</p>
            </div>
            <div class="flex space-x-2">
                <button onclick="window.print()" 
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg flex items-center transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                    </svg>
                    Print Receipt
                </button>
                <a href="{{ route('clinic.appointments.index') }}" 
                   class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition">
                    Back to Appointments
                </a>
            </div>
        </div>
        
        {{-- Receipt Card --}}
        <div class="print-area bg-white shadow-xl border border-gray-200 rounded-lg overflow-hidden print:shadow-none print:border-none max-w-none">
            
            {{-- Header --}}
            <div class="bg-blue-600 text-white px-6 py-4">
                <div class="flex justify-between items-center">
                    <div>
                        <h2 class="text-xl font-bold">{{ $clinic->clinic_name ?? 'Surigao Pet Doctors' }}</h2>
                        <p class="text-blue-100 text-sm mt-1">📍 {{ $clinic->address ?? 'QFQR+MM7, Burgos Street, corner Narciso Street, Surigao, 8400 Surigao del Norte' }}</p>
                        <p class="text-blue-100 text-sm">📞 {{ $clinic->contact_number ?? '09098987654' }}</p>
                    </div>
                    <div class="text-right">
                        <div class="text-2xl font-bold">RECEIPT</div>
                        <div class="text-blue-100">#{{ $receipt->receipt_number ?? 'RCPT-2025-00007' }}</div>
                        <div class="text-blue-100 text-sm">{{ $receipt->created_at ? $receipt->created_at->format('F d, Y') : now()->format('F d, Y') }}</div>
                    </div>
                </div>
            </div>

            {{-- Receipt Body --}}
            <div class="px-6 py-4">
                
                {{-- Patient & Payment Info --}}
                <div class="grid grid-cols-2 gap-6 mb-4">
                    {{-- Patient Information --}}
                    <div>
                        <h3 class="font-semibold text-gray-800 mb-2 text-sm border-b pb-1">Patient & Appointment Information</h3>
                        <div class="space-y-1 text-xs">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Patient Name:</span>
                                <span class="font-medium">{{ $receipt->patient_name ?? 'Azmira C. Laforteza' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Appointment ID:</span>
                                <span>#{{ $receipt->appointment_id ?? '9' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Phone:</span>
                                <span>{{ $receipt->appointment->owner_phone ?? '09879849768' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Service Date:</span>
                                <span>{{ $receipt->appointment && $receipt->appointment->appointment_date ? \Carbon\Carbon::parse($receipt->appointment->appointment_date)->format('M d, Y') : 'November 09, 2025' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Service Time:</span>
                                <span>{{ $receipt->appointment && $receipt->appointment->appointment_time ? \Carbon\Carbon::parse($receipt->appointment->appointment_time)->format('h:i A') : '01:00 PM' }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- Payment Information --}}
                    <div>
                        <h3 class="font-semibold text-gray-800 mb-2 text-sm border-b pb-1">Payment Information</h3>
                        <div class="space-y-1 text-xs">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Receipt Number:</span>
                                <span class="font-medium">{{ $receipt->receipt_number ?? 'RCPT-2025-00007' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Payment Method:</span>
                                <span class="capitalize">{{ str_replace('_', ' ', $receipt->payment_method ?? 'cash') }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Processed by:</span>
                                <span>{{ $receipt->processed_by ?? 'Sophia Laforteza' }}</span>
                            </div>
                            @if($receipt->doctor_name)
                            <div class="flex justify-between">
                                <span class="text-gray-600">Doctor:</span>
                                <span>{{ $receipt->doctor_name }}</span>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Service Summary --}}
                <div class="border-t pt-3 mb-3">
                    <h3 class="font-semibold text-gray-800 mb-2 text-sm">Service Summary</h3>
                    <div class="flex justify-between items-center bg-blue-50 p-3 rounded">
                        <div>
                            <div class="font-semibold">{{ $receipt->service_description ?? 'Veterinary Consultation' }}</div>
                            @if($receipt->doctor_name)
                                <div class="text-xs text-gray-600">by {{ $receipt->doctor_name }}</div>
                            @endif
                        </div>
                        <div class="text-right">
                            <div class="text-xl font-bold text-green-600">₱{{ number_format($receipt->amount ?? 500, 2) }}</div>
                        </div>
                    </div>

                    {{-- Custom Fields (Compact) --}}
                    @if($receipt->appointment && $receipt->appointment->customValues && $receipt->appointment->customValues->count() > 0)
                    <div class="mt-2 pt-2 border-t border-gray-100">
                        <div class="text-xs text-gray-600 grid grid-cols-2 gap-1">
                            @foreach($receipt->appointment->customValues->take(4) as $value)
                            <div>
                                <span class="font-medium">{{ $value->field->label ?? 'Field' }}:</span>
                                {{ is_array($value->value) ? implode(', ', $value->value) : ($value->value ?: 'N/A') }}
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div>

                {{-- Total --}}
                <div class="bg-green-100 border border-green-200 rounded p-2">
                    <div class="flex justify-between items-center">
                        <span class="font-bold text-gray-800">PAYMENT COMPLETED</span>
                        <span class="text-lg font-bold text-green-700">₱{{ number_format($receipt->amount ?? 500, 2) }}</span>
                    </div>
                </div>

                {{-- Notes (Compact) --}}
                @if($receipt->notes)
                <div class="mt-2">
                    <div class="text-xs bg-yellow-50 p-2 rounded border">
                        <span class="font-medium">Notes:</span> {{ $receipt->notes }}
                    </div>
                </div>
                @endif

                {{-- Footer --}}
                <div class="text-center text-xs text-gray-600 border-t pt-2 mt-3">
                    <p class="font-medium">Thank you for choosing {{ $clinic->clinic_name ?? 'Surigao Pet Doctors' }}!</p>
                </div>
            </div>
        </div>
    </div>

</body>
</html>