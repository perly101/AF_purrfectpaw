<?php

namespace App\Http\Controllers\Clinic;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\PaymentReceipt;
use App\Models\ClinicInfo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PaymentController extends Controller
{
    /**
     * Show the payment form for an appointment
     */
    public function create(Appointment $appointment)
    {
        $clinic = ClinicInfo::where('user_id', Auth::id())->firstOrFail();
        
        // Ensure the appointment belongs to this clinic
        if ($appointment->clinic_id !== $clinic->id) {
            abort(403, 'Unauthorized access to appointment');
        }
        
        // Check if appointment is completed and unpaid
        if ($appointment->status !== 'completed' || $appointment->payment_status === 'paid') {
            return redirect()->route('clinic.appointments.show', $appointment->id)
                ->with('error', 'Payment can only be processed for completed unpaid appointments.');
        }
        
        // Load appointment with custom values for display
        $appointment->load(['customValues.field', 'doctor']);
        
        // Get clinic's custom fields for service selection
        $clinicFields = \App\Models\ClinicField::where('clinic_id', $clinic->id)
            ->orderBy('order')
            ->get();
        
        return view('clinic.payments.create', compact('appointment', 'clinic', 'clinicFields'));
    }
    
    /**
     * Process the payment and generate receipt
     */
    public function store(Request $request, Appointment $appointment)
    {
        $clinic = ClinicInfo::where('user_id', Auth::id())->firstOrFail();
        
        // Ensure the appointment belongs to this clinic
        if ($appointment->clinic_id !== $clinic->id) {
            abort(403, 'Unauthorized access to appointment');
        }
        
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:cash,credit_card,debit_card,gcash,paymaya',
            'service_description' => 'required|string|max:255',
            'payment_notes' => 'nullable|string'
        ]);
        
        try {
            DB::beginTransaction();
            
            // Generate receipt number
            $receiptNumber = PaymentReceipt::generateReceiptNumber();
            
            // Update appointment with payment info
            $appointment->update([
                'payment_status' => 'paid',
                'amount' => $request->amount,
                'payment_method' => $request->payment_method,
                'receipt_number' => $receiptNumber,
                'payment_date' => Carbon::now(),
                'payment_notes' => $request->payment_notes
            ]);
            
            // Get the current user
            $currentUser = Auth::user();
            $processedBy = $currentUser->full_name ?? $currentUser->email ?? $clinic->clinic_name ?? 'Staff';
            
            // Create payment receipt record
            $receipt = PaymentReceipt::create([
                'receipt_number' => $receiptNumber,
                'appointment_id' => $appointment->id,
                'clinic_id' => $clinic->id,
                'doctor_id' => $appointment->doctor_id,
                'patient_name' => $appointment->owner_name,
                'doctor_name' => $appointment->doctor ? 
                    'Dr. ' . $appointment->doctor->first_name . ' ' . $appointment->doctor->last_name : null,
                'service_description' => $request->service_description,
                'amount' => $request->amount,
                'payment_method' => $request->payment_method,
                'payment_date' => Carbon::now(),
                'processed_by' => $processedBy,
                'notes' => $request->payment_notes
            ]);
            
            DB::commit();
            
            return redirect()->route('clinic.payments.receipt', $receipt->id)
                ->with('success', 'Payment processed successfully!');
                
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Payment processing failed: ' . $e->getMessage())
                ->withInput();
        }
    }
    
    /**
     * Show payment receipt
     */
    public function receipt(PaymentReceipt $receipt)
    {
        $clinic = ClinicInfo::where('user_id', Auth::id())->firstOrFail();
        
        // Ensure the receipt belongs to this clinic
        if ($receipt->clinic_id !== $clinic->id) {
            abort(403, 'Unauthorized access to receipt');
        }
        
        // Load appointment with all its relationships and custom values
        $receipt->load([
            'appointment.customValues.field',
            'appointment.doctor',
            'doctor'
        ]);
        
        return view('clinic.payments.receipt', compact('receipt', 'clinic'));
    }
    
    /**
     * Show payment summaries and daily reports
     */
    public function summary(Request $request)
    {
        $clinic = ClinicInfo::where('user_id', Auth::id())->firstOrFail();
        
        $date = $request->get('date', Carbon::today()->format('Y-m-d'));
        $startDate = Carbon::parse($date)->startOfDay();
        $endDate = Carbon::parse($date)->endOfDay();
        
        // Daily payments
        $dailyPayments = PaymentReceipt::where('clinic_id', $clinic->id)
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->with(['appointment', 'doctor'])
            ->orderBy('payment_date', 'desc')
            ->get();
            
        $dailyTotal = $dailyPayments->sum('amount');
        
        // Payment method breakdown
        $paymentMethodBreakdown = $dailyPayments->groupBy('payment_method')
            ->map(function ($payments) {
                return [
                    'count' => $payments->count(),
                    'total' => $payments->sum('amount')
                ];
            });
        
        // Monthly summary
        $monthStart = Carbon::parse($date)->startOfMonth();
        $monthEnd = Carbon::parse($date)->endOfMonth();
        
        $monthlyTotal = PaymentReceipt::where('clinic_id', $clinic->id)
            ->whereBetween('payment_date', [$monthStart, $monthEnd])
            ->sum('amount');
            
        $monthlyCount = PaymentReceipt::where('clinic_id', $clinic->id)
            ->whereBetween('payment_date', [$monthStart, $monthEnd])
            ->count();
        
        // Weekly chart data (last 7 days)
        $weeklyData = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = Carbon::parse($date)->subDays($i);
            $dayStart = $day->copy()->startOfDay();
            $dayEnd = $day->copy()->endOfDay();
            
            $dayTotal = PaymentReceipt::where('clinic_id', $clinic->id)
                ->whereBetween('payment_date', [$dayStart, $dayEnd])
                ->sum('amount');
                
            $weeklyData[] = [
                'date' => $day->format('M d'),
                'total' => $dayTotal
            ];
        }
        
        return view('clinic.payments.summary', compact(
            'clinic',
            'dailyPayments',
            'dailyTotal',
            'paymentMethodBreakdown',
            'monthlyTotal',
            'monthlyCount',
            'weeklyData',
            'date'
        ));
    }

    /**
     * Download comprehensive financial reports
     */
    public function downloadReport(Request $request)
    {
        $clinic = ClinicInfo::where('user_id', Auth::id())->firstOrFail();
        $type = $request->get('type', 'weekly');
        
        switch ($type) {
            case 'daily':
                return $this->generateDailyReport($clinic, $request);
            case 'weekly':
                return $this->generateWeeklyReport($clinic, $request);
            case 'monthly':
                return $this->generateMonthlyReport($clinic, $request);
            case 'annual':
                return $this->generateAnnualReport($clinic, $request);
            case 'custom':
                return $this->generateCustomReport($clinic, $request);
            default:
                abort(400, 'Invalid report type');
        }
    }

    private function generateDailyReport($clinic, $request)
    {
        $date = $request->get('date', now()->toDateString());
        $selectedDate = Carbon::parse($date);
        
        $payments = PaymentReceipt::where('clinic_id', $clinic->id)
            ->whereBetween('payment_date', [
                $selectedDate->copy()->startOfDay(),
                $selectedDate->copy()->endOfDay()
            ])
            ->with(['appointment'])
            ->orderBy('payment_date')
            ->get();

        $totalRevenue = $payments->sum('amount');
        $transactionCount = $payments->count();
        
        // Group by payment method
        $methodBreakdown = $payments->groupBy('payment_method')->map(function ($items) {
            return [
                'count' => $items->count(),
                'total' => $items->sum('amount')
            ];
        });

        // Hourly breakdown for the day
        $hourlyBreakdown = [];
        for ($hour = 0; $hour < 24; $hour++) {
            $hourStart = $selectedDate->copy()->setHour($hour)->startOfHour();
            $hourEnd = $hourStart->copy()->endOfHour();
            
            $hourPayments = $payments->filter(function ($payment) use ($hourStart, $hourEnd) {
                $paymentTime = Carbon::parse($payment->payment_date);
                return $paymentTime->between($hourStart, $hourEnd);
            });
            
            if ($hourPayments->count() > 0) {
                $hourlyBreakdown[] = [
                    'hour' => $hourStart->format('h:00 A'),
                    'total' => $hourPayments->sum('amount'),
                    'transactions' => $hourPayments->count()
                ];
            }
        }

        // Service breakdown
        $serviceBreakdown = $payments->groupBy('service_description')->map(function ($items, $service) {
            return [
                'service' => $service,
                'count' => $items->count(),
                'total' => $items->sum('amount')
            ];
        })->values()->sortByDesc('total');

        $csv = "DAILY FINANCIAL REPORT\n";
        $csv .= "Clinic: {$clinic->clinic_name}\n";
        $csv .= "Date: " . $selectedDate->format('F d, Y (l)') . "\n";
        $csv .= "Generated: " . now()->format('M d, Y h:i A') . "\n\n";
        
        $csv .= "SUMMARY\n";
        $csv .= "Total Revenue,₱" . number_format($totalRevenue, 2) . "\n";
        $csv .= "Total Transactions,{$transactionCount}\n";
        $csv .= "Average per Transaction,₱" . number_format($transactionCount > 0 ? $totalRevenue / $transactionCount : 0, 2) . "\n\n";
        
        if (!empty($hourlyBreakdown)) {
            $csv .= "HOURLY BREAKDOWN\n";
            $csv .= "Hour,Revenue,Transactions\n";
            foreach ($hourlyBreakdown as $hour) {
                $csv .= "\"{$hour['hour']}\",\"₱" . number_format($hour['total'], 2) . "\",\"{$hour['transactions']}\"\n";
            }
            $csv .= "\n";
        }
        
        $csv .= "PAYMENT METHODS\n";
        $csv .= "Method,Transactions,Total Amount\n";
        foreach ($methodBreakdown as $method => $data) {
            $methodName = ucfirst(str_replace('_', ' ', $method));
            $csv .= "\"{$methodName}\",\"{$data['count']}\",\"₱" . number_format($data['total'], 2) . "\"\n";
        }
        
        if ($serviceBreakdown->isNotEmpty()) {
            $csv .= "\nSERVICE BREAKDOWN\n";
            $csv .= "Service,Transactions,Total Amount\n";
            foreach ($serviceBreakdown as $service) {
                $csv .= "\"{$service['service']}\",\"{$service['count']}\",\"₱" . number_format($service['total'], 2) . "\"\n";
            }
        }
        
        $csv .= "\nDETAILED TRANSACTIONS\n";
        $csv .= "Receipt Number,Time,Patient Name,Service,Doctor,Payment Method,Amount\n";
        foreach ($payments as $payment) {
            $csv .= "\"{$payment->receipt_number}\",";
            $csv .= "\"" . Carbon::parse($payment->payment_date)->format('h:i A') . "\",";
            $csv .= "\"{$payment->patient_name}\",";
            $csv .= "\"{$payment->service_description}\",";
            $csv .= "\"" . ($payment->doctor_name ?? 'N/A') . "\",";
            $csv .= "\"" . ucfirst(str_replace('_', ' ', $payment->payment_method)) . "\",";
            $csv .= "\"₱" . number_format($payment->amount, 2) . "\"\n";
        }

        $filename = "daily_report_" . $selectedDate->format('Y-m-d') . ".csv";
        
        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    private function generateWeeklyReport($clinic, $request)
    {
        $endDate = $request->get('end', now()->toDateString());
        $startDate = $request->get('start', now()->subDays(6)->toDateString());
        
        $payments = PaymentReceipt::where('clinic_id', $clinic->id)
            ->whereBetween('payment_date', [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay()
            ])
            ->with(['appointment'])
            ->orderBy('payment_date')
            ->get();

        $totalRevenue = $payments->sum('amount');
        $avgPerDay = $totalRevenue / 7;
        $transactionCount = $payments->count();
        
        // Group by payment method
        $methodBreakdown = $payments->groupBy('payment_method')->map(function ($items) {
            return [
                'count' => $items->count(),
                'total' => $items->sum('amount')
            ];
        });

        // Daily breakdown
        $dailyBreakdown = [];
        for ($i = 0; $i < 7; $i++) {
            $day = Carbon::parse($startDate)->addDays($i);
            $dayPayments = $payments->filter(function ($payment) use ($day) {
                return Carbon::parse($payment->payment_date)->isSameDay($day);
            });
            
            $dailyBreakdown[] = [
                'date' => $day->format('M d, Y'),
                'day_name' => $day->format('l'),
                'total' => $dayPayments->sum('amount'),
                'transactions' => $dayPayments->count()
            ];
        }

        $csv = "WEEKLY FINANCIAL REPORT\n";
        $csv .= "Clinic: {$clinic->clinic_name}\n";
        $csv .= "Period: " . Carbon::parse($startDate)->format('M d, Y') . " to " . Carbon::parse($endDate)->format('M d, Y') . "\n";
        $csv .= "Generated: " . now()->format('M d, Y h:i A') . "\n\n";
        
        $csv .= "SUMMARY\n";
        $csv .= "Total Revenue,PHP " . number_format($totalRevenue, 2) . "\n";
        $csv .= "Total Transactions,{$transactionCount}\n";
        $csv .= "Average per Day,PHP " . number_format($avgPerDay, 2) . "\n";
        $csv .= "Average per Transaction,PHP " . number_format($transactionCount > 0 ? $totalRevenue / $transactionCount : 0, 2) . "\n\n";
        
        $csv .= "DAILY BREAKDOWN\n";
        $csv .= "Date,Day,Revenue,Transactions\n";
        foreach ($dailyBreakdown as $day) {
            $csv .= "\"{$day['date']}\",\"{$day['day_name']}\",\"PHP " . number_format($day['total'], 2) . "\",\"{$day['transactions']}\"\n";
        }
        
        $csv .= "\nPAYMENT METHODS\n";
        $csv .= "Method,Transactions,Total Amount\n";
        foreach ($methodBreakdown as $method => $data) {
            $methodName = ucfirst(str_replace('_', ' ', $method));
            $csv .= "\"{$methodName}\",\"{$data['count']}\",\"PHP " . number_format($data['total'], 2) . "\"\n";
        }
        
        $csv .= "\nDETAILED TRANSACTIONS\n";
        $csv .= "Receipt Number,Date,Time,Patient Name,Service,Doctor,Payment Method,Amount\n";
        foreach ($payments as $payment) {
            $csv .= "\"{$payment->receipt_number}\",";
            $csv .= "\"" . Carbon::parse($payment->payment_date)->format('M d, Y') . "\",";
            $csv .= "\"" . Carbon::parse($payment->payment_date)->format('h:i A') . "\",";
            $csv .= "\"{$payment->patient_name}\",";
            $csv .= "\"{$payment->service_description}\",";
            $csv .= "\"" . ($payment->doctor_name ?? 'N/A') . "\",";
            $csv .= "\"" . ucfirst(str_replace('_', ' ', $payment->payment_method)) . "\",";
            $csv .= "\"PHP " . number_format($payment->amount, 2) . "\"\n";
        }

        $filename = "weekly_report_" . Carbon::parse($startDate)->format('Y-m-d') . "_to_" . Carbon::parse($endDate)->format('Y-m-d') . ".csv";
        
        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    private function generateMonthlyReport($clinic, $request)
    {
        $month = $request->get('month', now()->month);
        $year = $request->get('year', now()->year);
        
        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();
        
        $payments = PaymentReceipt::where('clinic_id', $clinic->id)
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->with(['appointment'])
            ->orderBy('payment_date')
            ->get();

        $totalRevenue = $payments->sum('amount');
        $transactionCount = $payments->count();
        $daysInMonth = $endDate->day;
        $avgPerDay = $totalRevenue / $daysInMonth;
        
        // Weekly breakdown for the month
        $weeklyBreakdown = [];
        $currentWeekStart = $startDate->copy()->startOfWeek();
        
        while ($currentWeekStart->lessThanOrEqualTo($endDate)) {
            $weekEnd = $currentWeekStart->copy()->endOfWeek();
            if ($weekEnd->greaterThan($endDate)) {
                $weekEnd = $endDate->copy();
            }
            
            $weekPayments = $payments->filter(function ($payment) use ($currentWeekStart, $weekEnd) {
                $paymentDate = Carbon::parse($payment->payment_date);
                return $paymentDate->between($currentWeekStart, $weekEnd);
            });
            
            $weeklyBreakdown[] = [
                'week' => "Week " . $currentWeekStart->weekOfMonth,
                'period' => $currentWeekStart->format('M d') . ' - ' . $weekEnd->format('M d'),
                'total' => $weekPayments->sum('amount'),
                'transactions' => $weekPayments->count()
            ];
            
            $currentWeekStart->addWeek();
        }

        $csv = "MONTHLY FINANCIAL REPORT\n";
        $csv .= "Clinic: {$clinic->clinic_name}\n";
        $csv .= "Month: " . $startDate->format('F Y') . "\n";
        $csv .= "Generated: " . now()->format('M d, Y h:i A') . "\n\n";
        
        $csv .= "SUMMARY\n";
        $csv .= "Total Revenue,₱" . number_format($totalRevenue, 2) . "\n";
        $csv .= "Total Transactions,{$transactionCount}\n";
        $csv .= "Days in Month,{$daysInMonth}\n";
        $csv .= "Average per Day,₱" . number_format($avgPerDay, 2) . "\n";
        $csv .= "Average per Transaction,₱" . number_format($transactionCount > 0 ? $totalRevenue / $transactionCount : 0, 2) . "\n\n";
        
        $csv .= "WEEKLY BREAKDOWN\n";
        $csv .= "Week,Period,Revenue,Transactions\n";
        foreach ($weeklyBreakdown as $week) {
            $csv .= "\"{$week['week']}\",\"{$week['period']}\",\"₱" . number_format($week['total'], 2) . "\",\"{$week['transactions']}\"\n";
        }
        
        // Add payment methods and detailed transactions like in weekly report
        $methodBreakdown = $payments->groupBy('payment_method')->map(function ($items) {
            return [
                'count' => $items->count(),
                'total' => $items->sum('amount')
            ];
        });
        
        $csv .= "\nPAYMENT METHODS\n";
        $csv .= "Method,Transactions,Total Amount\n";
        foreach ($methodBreakdown as $method => $data) {
            $methodName = ucfirst(str_replace('_', ' ', $method));
            $csv .= "\"{$methodName}\",\"{$data['count']}\",\"₱" . number_format($data['total'], 2) . "\"\n";
        }

        $filename = "monthly_report_" . $startDate->format('Y-m') . ".csv";
        
        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    private function generateAnnualReport($clinic, $request)
    {
        $year = $request->get('year', now()->year);
        
        $startDate = Carbon::createFromDate($year, 1, 1)->startOfYear();
        $endDate = $startDate->copy()->endOfYear();
        
        $payments = PaymentReceipt::where('clinic_id', $clinic->id)
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->with(['appointment'])
            ->orderBy('payment_date')
            ->get();

        $totalRevenue = $payments->sum('amount');
        $transactionCount = $payments->count();
        $avgPerMonth = $totalRevenue / 12;
        
        // Monthly breakdown for the year
        $monthlyBreakdown = [];
        for ($month = 1; $month <= 12; $month++) {
            $monthStart = Carbon::createFromDate($year, $month, 1)->startOfMonth();
            $monthEnd = $monthStart->copy()->endOfMonth();
            
            $monthPayments = $payments->filter(function ($payment) use ($monthStart, $monthEnd) {
                $paymentDate = Carbon::parse($payment->payment_date);
                return $paymentDate->between($monthStart, $monthEnd);
            });
            
            $monthlyBreakdown[] = [
                'month' => $monthStart->format('F'),
                'total' => $monthPayments->sum('amount'),
                'transactions' => $monthPayments->count(),
                'avg_per_day' => $monthPayments->sum('amount') / $monthEnd->day
            ];
        }
        
        // Quarterly breakdown
        $quarterlyBreakdown = [];
        for ($quarter = 1; $quarter <= 4; $quarter++) {
            $qStart = Carbon::createFromDate($year, ($quarter - 1) * 3 + 1, 1)->startOfQuarter();
            $qEnd = $qStart->copy()->endOfQuarter();
            
            $qPayments = $payments->filter(function ($payment) use ($qStart, $qEnd) {
                $paymentDate = Carbon::parse($payment->payment_date);
                return $paymentDate->between($qStart, $qEnd);
            });
            
            $quarterlyBreakdown[] = [
                'quarter' => "Q{$quarter}",
                'period' => $qStart->format('M') . ' - ' . $qEnd->format('M'),
                'total' => $qPayments->sum('amount'),
                'transactions' => $qPayments->count()
            ];
        }

        $csv = "ANNUAL FINANCIAL REPORT\n";
        $csv .= "Clinic: {$clinic->clinic_name}\n";
        $csv .= "Year: {$year}\n";
        $csv .= "Generated: " . now()->format('M d, Y h:i A') . "\n\n";
        
        $csv .= "SUMMARY\n";
        $csv .= "Total Revenue,₱" . number_format($totalRevenue, 2) . "\n";
        $csv .= "Total Transactions,{$transactionCount}\n";
        $csv .= "Average per Month,₱" . number_format($avgPerMonth, 2) . "\n";
        $csv .= "Average per Transaction,₱" . number_format($transactionCount > 0 ? $totalRevenue / $transactionCount : 0, 2) . "\n\n";
        
        $csv .= "QUARTERLY BREAKDOWN\n";
        $csv .= "Quarter,Period,Revenue,Transactions\n";
        foreach ($quarterlyBreakdown as $quarter) {
            $csv .= "\"{$quarter['quarter']}\",\"{$quarter['period']}\",\"₱" . number_format($quarter['total'], 2) . "\",\"{$quarter['transactions']}\"\n";
        }
        
        $csv .= "\nMONTHLY BREAKDOWN\n";
        $csv .= "Month,Revenue,Transactions,Avg per Day\n";
        foreach ($monthlyBreakdown as $month) {
            $csv .= "\"{$month['month']}\",\"₱" . number_format($month['total'], 2) . "\",\"{$month['transactions']}\",\"₱" . number_format($month['avg_per_day'], 2) . "\"\n";
        }

        $filename = "annual_report_{$year}.csv";
        
        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    private function generateCustomReport($clinic, $request)
    {
        $startDate = Carbon::parse($request->get('start'));
        $endDate = Carbon::parse($request->get('end'));
        
        $payments = PaymentReceipt::where('clinic_id', $clinic->id)
            ->whereBetween('payment_date', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->with(['appointment'])
            ->orderBy('payment_date')
            ->get();

        $totalRevenue = $payments->sum('amount');
        $transactionCount = $payments->count();
        $daysDiff = $startDate->diffInDays($endDate) + 1;
        $avgPerDay = $totalRevenue / $daysDiff;

        $csv = "CUSTOM PERIOD FINANCIAL REPORT\n";
        $csv .= "Clinic: {$clinic->clinic_name}\n";
        $csv .= "Period: " . $startDate->format('M d, Y') . " to " . $endDate->format('M d, Y') . "\n";
        $csv .= "Duration: {$daysDiff} days\n";
        $csv .= "Generated: " . now()->format('M d, Y h:i A') . "\n\n";
        
        $csv .= "SUMMARY\n";
        $csv .= "Total Revenue,₱" . number_format($totalRevenue, 2) . "\n";
        $csv .= "Total Transactions,{$transactionCount}\n";
        $csv .= "Average per Day,₱" . number_format($avgPerDay, 2) . "\n";
        $csv .= "Average per Transaction,₱" . number_format($transactionCount > 0 ? $totalRevenue / $transactionCount : 0, 2) . "\n\n";
        
        $csv .= "DETAILED TRANSACTIONS\n";
        $csv .= "Receipt Number,Date,Time,Patient Name,Service,Doctor,Payment Method,Amount\n";
        foreach ($payments as $payment) {
            $csv .= "\"{$payment->receipt_number}\",";
            $csv .= "\"" . Carbon::parse($payment->payment_date)->format('M d, Y') . "\",";
            $csv .= "\"" . Carbon::parse($payment->payment_date)->format('h:i A') . "\",";
            $csv .= "\"{$payment->patient_name}\",";
            $csv .= "\"{$payment->service_description}\",";
            $csv .= "\"" . ($payment->doctor_name ?? 'N/A') . "\",";
            $csv .= "\"" . ucfirst(str_replace('_', ' ', $payment->payment_method)) . "\",";
            $csv .= "\"₱" . number_format($payment->amount, 2) . "\"\n";
        }

        $filename = "custom_report_" . $startDate->format('Y-m-d') . "_to_" . $endDate->format('Y-m-d') . ".csv";
        
        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }
}
