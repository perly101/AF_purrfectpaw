@php
    use App\Models\ClinicInfo;
    $clinic = $clinic ?? ClinicInfo::where('user_id', auth()->id())->first();
@endphp

<x-app-layout>
    <div class="py-2 sm:py-6 bg-gray-100 min-h-screen">
        <div class="px-2 sm:px-4 lg:px-8">
            
            {{-- Mobile/Desktop Layout Container --}}
            <div class="flex flex-col lg:flex-row">
                
                {{-- Sidebar - Hidden on mobile, shown on desktop --}}
                <div class="hidden lg:block w-64 flex-shrink-0 mr-6">
                    @include('clinic.components.sidebar')
                </div>
                
                {{-- Main Content --}}
                <div class="flex-1 w-full min-w-0">
                    
                    {{-- Header Section --}}
                    <div class="mb-4 sm:mb-6">
                        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start gap-4">
                            <div class="min-w-0 flex-1">
                                <h2 class="text-xl sm:text-2xl font-semibold text-gray-800 truncate">Payment Reports</h2>
                                <p class="text-gray-500 text-xs sm:text-sm mt-1">Daily payment summaries and financial reports</p>
                            </div>
                            
                            {{-- Controls Section - Stack on mobile --}}
                            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 sm:gap-4 w-full sm:w-auto">
                                {{-- Date Filter --}}
                                <form method="GET" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 w-full sm:w-auto">
                                    <label for="date" class="text-sm font-medium text-gray-700 sm:whitespace-nowrap">Date:</label>
                                    <input type="date" 
                                           id="date" 
                                           name="date" 
                                           value="{{ $date }}"
                                           class="flex-1 sm:flex-none rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                    <button type="submit" 
                                            class="px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md transition text-sm whitespace-nowrap">
                                        Filter
                                    </button>
                                </form>

                                {{-- Report Downloads --}}
                                <div class="relative w-full sm:w-auto">
                                    <button onclick="toggleReportDropdown()" 
                                            class="w-full sm:w-auto px-3 py-2 bg-green-600 hover:bg-green-700 text-white rounded-md transition flex items-center justify-center space-x-2 text-sm">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        <span class="hidden sm:inline">Download Reports</span>
                                        <span class="sm:hidden">Reports</span>
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </button>

                                    <div id="reportDropdown" class="hidden absolute right-0 sm:right-0 left-0 sm:left-auto mt-2 w-full sm:w-80 bg-white rounded-md shadow-lg border border-gray-200 z-50 max-h-96 overflow-y-auto">
                                    <div class="p-4">
                                        <h4 class="font-semibold text-gray-800 mb-3">Download Financial Reports</h4>
                                        
                                        {{-- Daily Report --}}
                                        <div class="mb-3 p-3 border border-gray-200 rounded">
                                            <div class="flex flex-col gap-2 mb-2">
                                                <h5 class="font-medium text-gray-700 text-sm">Daily Report</h5>
                                                <div class="flex flex-col gap-2">
                                                    <input type="date" id="dailyReportDate" class="text-xs border-gray-300 rounded" 
                                                           value="{{ $date }}">
                                                    <button onclick="downloadDailyReport()" 
                                                            class="px-3 py-1 bg-indigo-600 hover:bg-indigo-700 text-white text-xs rounded transition w-full">
                                                        Download
                                                    </button>
                                                </div>
                                            </div>
                                            <p class="text-xs text-gray-500">Complete daily transactions and financial summary</p>
                                        </div>

                                        {{-- Weekly Report --}}
                                        <div class="mb-3 p-3 border border-gray-200 rounded">
                                            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-2 mb-2">
                                                <h5 class="font-medium text-gray-700 text-sm">Weekly Report</h5>
                                                <button onclick="downloadWeeklyReport()" 
                                                        class="px-3 py-1 bg-blue-600 hover:bg-blue-700 text-white text-xs rounded transition w-full sm:w-auto">
                                                    Download
                                                </button>
                                            </div>
                                            <p class="text-xs text-gray-500">Last 7 days income summary with daily breakdown</p>
                                        </div>

                                      {{-- Monthly Report --}}
<div class="mb-3 p-3 border border-gray-200 rounded">
    <div class="flex flex-col gap-2 mb-2">
        <h5 class="font-medium text-gray-700 text-sm">Monthly Report</h5>
        
        <!-- Fields stacked -->
        <div class="flex flex-col gap-2">
            <select id="monthSelect" class="text-xs border-gray-300 rounded">
                @for($i = 1; $i <= 12; $i++)
                    <option value="{{ $i }}" {{ date('n') == $i ? 'selected' : '' }}>
                        {{ date('F', mktime(0, 0, 0, $i, 1)) }}
                    </option>
                @endfor
            </select>

            <select id="yearSelect" class="text-xs border-gray-300 rounded">
                @for($year = date('Y') - 2; $year <= date('Y'); $year++)
                    <option value="{{ $year }}" {{ date('Y') == $year ? 'selected' : '' }}>
                        {{ $year }}
                    </option>
                @endfor
            </select>

            <!-- Button below -->
            <button onclick="downloadMonthlyReport()" 
                    class="px-3 py-1 bg-purple-600 hover:bg-purple-700 text-white text-xs rounded transition w-full">
                Download
            </button>
        </div>
    </div>

    <p class="text-xs text-gray-500">Complete monthly financial report with analytics</p>
</div>


                                        {{-- Annual Report --}}
                                        <div class="mb-3 p-3 border border-gray-200 rounded">
                                            <div class="flex flex-col gap-2 mb-2">
                                                <h5 class="font-medium text-gray-700 text-sm">Annual Report</h5>
                                                <div class="flex flex-col sm:flex-row gap-2">
                                                    <select id="annualYearSelect" class="text-xs border-gray-300 rounded flex-1">
                                                        @for($year = date('Y') - 5; $year <= date('Y'); $year++)
                                                            <option value="{{ $year }}" {{ date('Y') == $year ? 'selected' : '' }}>{{ $year }}</option>
                                                        @endfor
                                                    </select>
                                                    <button onclick="downloadAnnualReport()" 
                                                            class="px-3 py-1 bg-orange-600 hover:bg-orange-700 text-white text-xs rounded transition w-full sm:w-auto">
                                                        Download
                                                    </button>
                                                </div>
                                            </div>
                                            <p class="text-xs text-gray-500">Yearly performance report with growth analysis</p>
                                        </div>

                                        {{-- Custom Date Range --}}
                                        <div class="p-3 border border-gray-200 rounded">
                                            <div class="flex flex-col gap-2 mb-2">
                                                <h5 class="font-medium text-gray-700 text-sm">Custom Range</h5>
                                                <div class="flex flex-col gap-2">
                                                    <div class="flex flex-col sm:flex-row gap-2">
                                                        <input type="date" id="customStartDate" class="text-xs border-gray-300 rounded flex-1" 
                                                               value="{{ now()->subDays(30)->format('Y-m-d') }}">
                                                        <input type="date" id="customEndDate" class="text-xs border-gray-300 rounded flex-1" 
                                                               value="{{ now()->format('Y-m-d') }}">
                                                    </div>
                                                    <button onclick="downloadCustomReport()" 
                                                            class="px-3 py-1 bg-gray-600 hover:bg-gray-700 text-white text-xs rounded transition w-full sm:w-auto">
                                                        Download
                                                    </button>
                                                </div>
                                            </div>
                                            <p class="text-xs text-gray-500">Choose your own date range for analysis</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                    {{-- Summary Cards --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-6 mb-6 sm:mb-8">
                        <div class="bg-white rounded-lg shadow-lg border border-gray-200 p-4 sm:p-6">
                            <div class="flex items-center">
                                <div class="p-2 sm:p-3 rounded-full bg-green-100 text-green-600 flex-shrink-0">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 sm:h-8 sm:w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                                    </svg>
                                </div>
                                <div class="ml-3 sm:ml-4 min-w-0 flex-1">
                                    <p class="text-xs sm:text-sm font-medium text-gray-500">Daily Total</p>
                                    <p class="text-sm sm:text-base font-bold text-gray-900 truncate">₱{{ number_format($dailyTotal, 2) }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white rounded-lg shadow-lg border border-gray-200 p-4 sm:p-6">
                            <div class="flex items-center">
                                <div class="p-2 sm:p-3 rounded-full bg-blue-100 text-blue-600 flex-shrink-0">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 sm:h-8 sm:w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <div class="ml-3 sm:ml-4 min-w-0 flex-1">
                                    <p class="text-xs sm:text-sm font-medium text-gray-500">Transactions</p>
                                    <p class="text-sm sm:text-base font-bold text-gray-900">{{ $dailyPayments->count() }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white rounded-lg shadow-lg border border-gray-200 p-4 sm:p-6">
                            <div class="flex items-center">
                                <div class="p-2 sm:p-3 rounded-full bg-purple-100 text-purple-600 flex-shrink-0">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 sm:h-8 sm:w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <div class="ml-3 sm:ml-4 min-w-0 flex-1">
                                    <p class="text-xs sm:text-sm font-medium text-gray-500">Monthly Total</p>
                                    <p class="text-sm sm:text-base font-bold text-gray-900 truncate">₱{{ number_format($monthlyTotal, 2) }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white rounded-lg shadow-lg border border-gray-200 p-4 sm:p-6">
                            <div class="flex items-center">
                                <div class="p-2 sm:p-3 rounded-full bg-orange-100 text-orange-600 flex-shrink-0">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 sm:h-8 sm:w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                    </svg>
                                </div>
                                <div class="ml-3 sm:ml-4 min-w-0 flex-1">
                                    <p class="text-xs sm:text-sm font-medium text-gray-500">Avg. Per Transaction</p>
                                    <p class="text-sm sm:text-base font-bold text-gray-900 truncate">
                                        @if($dailyPayments->count() > 0)
                                            ₱{{ number_format($dailyTotal / $dailyPayments->count(), 2) }}
                                        @else
                                            ₱0.00
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </div>
                </div>

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6 mb-6 sm:mb-8">
                        {{-- Weekly Chart --}}
                        <div class="lg:col-span-2 bg-white rounded-lg shadow-lg border border-gray-200 p-4 sm:p-6">
                            <h3 class="text-base sm:text-lg font-semibold text-gray-800 mb-4">Weekly Revenue Trend</h3>
                            <div style="height: 250px;" class="sm:h-80">
                                <canvas id="weeklyChart"></canvas>
                            </div>
                        </div>

                        {{-- Payment Method Breakdown --}}
                        <div class="bg-white rounded-lg shadow-lg border border-gray-200 p-4 sm:p-6">
                            <h3 class="text-base sm:text-lg font-semibold text-gray-800 mb-4">Payment Methods</h3>
                            <div class="space-y-3">
                                @forelse($paymentMethodBreakdown as $method => $data)
                                <div class="flex justify-between items-center">
                                    <div class="min-w-0 flex-1">
                                        <span class="text-xs sm:text-sm font-medium text-gray-700 block truncate">{{ ucfirst(str_replace('_', ' ', $method)) }}</span>
                                        <span class="text-xs text-gray-500 block">{{ $data['count'] }} transactions</span>
                                    </div>
                                    <span class="text-xs sm:text-sm font-bold text-gray-900 ml-2">₱{{ number_format($data['total'], 2) }}</span>
                                </div>
                                @empty
                                <p class="text-gray-500 text-xs sm:text-sm text-center py-4">No payments for this date</p>
                                @endforelse
                        </div>
                    </div>
                </div>

                    {{-- Daily Transactions Table --}}
                    <div class="bg-white rounded-lg shadow-lg border border-gray-200 overflow-hidden">
                        <div class="px-4 sm:px-6 py-3 sm:py-4 border-b bg-gray-50">
                            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-2 sm:gap-0">
                                <h3 class="text-base sm:text-lg font-semibold text-gray-800">
                                    Daily Transactions - {{ \Carbon\Carbon::parse($date)->format('M d, Y') }}
                                </h3>
                                @if($dailyPayments->count() > 0)
                                <button onclick="exportToCSV()" 
                                        class="text-xs sm:text-sm px-3 py-1 bg-green-600 hover:bg-green-700 text-white rounded transition w-full sm:w-auto">
                                    Export CSV
                                </button>
                                @endif
                            </div>
                        </div>

                        {{-- Mobile Card Layout (shown on small screens) --}}
                        <div class="block sm:hidden">
                            @forelse($dailyPayments as $payment)
                            <div class="border-b border-gray-200 p-4">
                                <div class="flex justify-between items-start mb-2">
                                    <div class="flex-1 min-w-0">
                                        <a href="{{ route('clinic.payments.receipt', $payment->id) }}" class="text-sm font-medium text-blue-600 hover:text-blue-900 block truncate">
                                            {{ $payment->receipt_number }}
                                        </a>
                                        <p class="text-sm text-gray-900 truncate">{{ $payment->patient_name }}</p>
                                    </div>
                                    <span class="text-sm font-semibold text-green-600 ml-2">₱{{ number_format($payment->amount, 2) }}</span>
                                </div>
                                <div class="grid grid-cols-2 gap-2 text-xs text-gray-600">
                                    <div>
                                        <span class="font-medium">Service:</span>
                                        <p class="truncate">{{ $payment->service_description }}</p>
                                    </div>
                                    <div>
                                        <span class="font-medium">Doctor:</span>
                                        <p class="truncate">{{ $payment->doctor_name ?? 'N/A' }}</p>
                                    </div>
                                    <div>
                                        <span class="font-medium">Method:</span>
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                            {{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}
                                        </span>
                                    </div>
                                    <div>
                                        <span class="font-medium">Time:</span>
                                        <p>{{ $payment->payment_date->format('h:i A') }}</p>
                                    </div>
                                </div>
                            </div>
                            @empty
                            <div class="p-6 text-center text-gray-500">
                                <div class="flex flex-col items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                    </svg>
                                    <p class="text-sm">No payments recorded for {{ \Carbon\Carbon::parse($date)->format('M d, Y') }}</p>
                                </div>
                            </div>
                            @endforelse
                        </div>

                        {{-- Desktop Table Layout (hidden on small screens) --}}
                        <div class="hidden sm:block overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Receipt</th>
                                        <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Patient</th>
                                        <th class="hidden md:table-cell px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Service</th>
                                        <th class="hidden lg:table-cell px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Doctor</th>
                                        <th class="hidden md:table-cell px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payment Method</th>
                                        <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                        <th class="hidden sm:table-cell px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                                        <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @forelse($dailyPayments as $payment)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-3 sm:px-6 py-4 whitespace-nowrap text-xs sm:text-sm font-medium text-blue-600">
                                            <a href="{{ route('clinic.payments.receipt', $payment->id) }}" class="hover:text-blue-900">
                                                {{ $payment->receipt_number }}
                                            </a>
                                        </td>
                                        <td class="px-3 sm:px-6 py-4 text-xs sm:text-sm text-gray-900">
                                            <div class="truncate max-w-32 sm:max-w-none">{{ $payment->patient_name }}</div>
                                        </td>
                                        <td class="hidden md:table-cell px-3 sm:px-6 py-4 text-xs sm:text-sm text-gray-900">
                                            <div class="truncate max-w-40">{{ $payment->service_description }}</div>
                                        </td>
                                        <td class="hidden lg:table-cell px-3 sm:px-6 py-4 whitespace-nowrap text-xs sm:text-sm text-gray-900">
                                            {{ $payment->doctor_name ?? 'N/A' }}
                                        </td>
                                        <td class="hidden md:table-cell px-3 sm:px-6 py-4 whitespace-nowrap text-xs sm:text-sm text-gray-900">
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                                {{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}
                                            </span>
                                        </td>
                                        <td class="px-3 sm:px-6 py-4 whitespace-nowrap text-xs sm:text-sm font-semibold text-green-600">
                                            ₱{{ number_format($payment->amount, 2) }}
                                        </td>
                                        <td class="hidden sm:table-cell px-3 sm:px-6 py-4 whitespace-nowrap text-xs sm:text-sm text-gray-500">
                                            {{ $payment->payment_date->format('h:i A') }}
                                        </td>
                                        <td class="px-3 sm:px-6 py-4 whitespace-nowrap text-xs sm:text-sm text-gray-500">
                                            <a href="{{ route('clinic.payments.receipt', $payment->id) }}" 
                                               class="text-blue-600 hover:text-blue-900">View</a>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="8" class="px-6 py-8 text-center text-gray-500">
                                            <div class="flex flex-col items-center">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                                </svg>
                                                <p class="text-sm">No payments recorded for {{ \Carbon\Carbon::parse($date)->format('M d, Y') }}</p>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Chart.js --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <script>
        // Weekly Chart
        const weeklyCtx = document.getElementById('weeklyChart').getContext('2d');
        const weeklyData = @json($weeklyData);
        
        new Chart(weeklyCtx, {
            type: 'line',
            data: {
                labels: weeklyData.map(d => d.date),
                datasets: [{
                    label: 'Daily Revenue',
                    data: weeklyData.map(d => d.total),
                    borderColor: 'rgb(59, 130, 246)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    x: {
                        ticks: {
                            font: {
                                size: window.innerWidth < 640 ? 10 : 12
                            }
                        }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            font: {
                                size: window.innerWidth < 640 ? 10 : 12
                            },
                            callback: function(value) {
                                if (window.innerWidth < 640) {
                                    return '₱' + (value >= 1000 ? (value/1000).toFixed(1) + 'k' : value);
                                }
                                return '₱' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });

        // Export to CSV function
        function exportToCSV() {
            const payments = @json($dailyPayments);
            let csv = 'Receipt Number,Patient Name,Service,Doctor,Payment Method,Amount,Payment Date,Time\n';
            
            payments.forEach(payment => {
                csv += `"${payment.receipt_number}","${payment.patient_name}","${payment.service_description}","${payment.doctor_name || 'N/A'}","${payment.payment_method}","${payment.amount}","${new Date(payment.payment_date).toLocaleDateString()}","${new Date(payment.payment_date).toLocaleTimeString()}"\n`;
            });
            
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `payments_{{ $date }}.csv`;
            a.click();
            window.URL.revokeObjectURL(url);
        }

        // Toggle report dropdown
        function toggleReportDropdown() {
            const dropdown = document.getElementById('reportDropdown');
            dropdown.classList.toggle('hidden');
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const dropdown = document.getElementById('reportDropdown');
            const button = event.target.closest('[onclick="toggleReportDropdown()"]');
            
            if (!button && !dropdown.contains(event.target)) {
                dropdown.classList.add('hidden');
            }
        });

        // Daily Report Download
        function downloadDailyReport() {
            const selectedDate = document.getElementById('dailyReportDate').value;
            
            if (!selectedDate) {
                alert('Please select a date for the daily report');
                return;
            }
            
            const url = `/clinic/payments/download-report?type=daily&date=${selectedDate}`;
            window.location.href = url;
        }

        // Weekly Report Download
        function downloadWeeklyReport() {
            const endDate = new Date();
            const startDate = new Date();
            startDate.setDate(startDate.getDate() - 6);
            
            const url = `/clinic/payments/download-report?type=weekly&start=${startDate.toISOString().split('T')[0]}&end=${endDate.toISOString().split('T')[0]}`;
            window.location.href = url;
        }

        // Monthly Report Download
        function downloadMonthlyReport() {
            const month = document.getElementById('monthSelect').value;
            const year = document.getElementById('yearSelect').value;
            
            const url = `/clinic/payments/download-report?type=monthly&month=${month}&year=${year}`;
            window.location.href = url;
        }

        // Annual Report Download
        function downloadAnnualReport() {
            const year = document.getElementById('annualYearSelect').value;
            
            const url = `/clinic/payments/download-report?type=annual&year=${year}`;
            window.location.href = url;
        }

        // Custom Range Report Download
        function downloadCustomReport() {
            const startDate = document.getElementById('customStartDate').value;
            const endDate = document.getElementById('customEndDate').value;
            
            if (!startDate || !endDate) {
                alert('Please select both start and end dates');
                return;
            }
            
            if (new Date(startDate) > new Date(endDate)) {
                alert('Start date cannot be later than end date');
                return;
            }
            
            const url = `/clinic/payments/download-report?type=custom&start=${startDate}&end=${endDate}`;
            window.location.href = url;
        }
    </script>
</x-app-layout>