<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use App\Models\Appointment;
use App\Models\PaymentReceipt;

class ProfileController extends Controller
{
    /**
     * Display the authenticated user's profile
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request)
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json([
            'id' => $user->id,
            'first_name' => $user->first_name,
            'middle_name' => $user->middle_name,
            'last_name' => $user->last_name,
            'name' => trim(($user->first_name ?? '') . ' ' . ($user->middle_name ?? '') . ' ' . ($user->last_name ?? '')), // for backward compatibility
            'email' => $user->email,
            'phone_number' => $user->phone_number,
            'gender' => $user->gender,
            'birthday' => $user->birthday,
            'role' => $user->role,
        ]);
    }

    /**
     * Update the authenticated user's profile
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request)
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone_number' => ['nullable', 'string', 'max:20'],
            'gender' => ['nullable', 'in:female,male,prefer_not_say'],
            'birthday' => ['nullable', 'date'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        // Accept legacy 'name' field for backward compatibility
        $legacyName = $request->input('name');
        if (!empty($legacyName) && empty($validated['first_name'])) {
            $nameParts = explode(' ', trim($legacyName), 3);
            $user->first_name = $nameParts[0] ?? '';
            $user->middle_name = count($nameParts) > 2 ? $nameParts[1] : null;
            $user->last_name = count($nameParts) > 1 ? end($nameParts) : '';
        } else {
            // Use the new individual name fields
            $user->first_name = $validated['first_name'];
            $user->middle_name = $validated['middle_name'];
            $user->last_name = $validated['last_name'];
        }

        $user->email = $validated['email'];
        $user->phone_number = $validated['phone_number'] ?? null;
        $user->gender = $validated['gender'] ?? null;
        $user->birthday = $validated['birthday'] ?? null;

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return response()->json([
            'message' => 'Profile updated',
            'id' => $user->id,
            'first_name' => $user->first_name,
            'middle_name' => $user->middle_name,
            'last_name' => $user->last_name,
            'name' => trim(($user->first_name ?? '') . ' ' . ($user->middle_name ?? '') . ' ' . ($user->last_name ?? '')),
            'email' => $user->email,
            'phone_number' => $user->phone_number,
            'gender' => $user->gender,
            'birthday' => $user->birthday,
            'role' => $user->role,
        ]);
    }

    /**
     * Return all appointments that belong to the authenticated user.
     * Matching is done by phone number (preferred) and by owner name as fallback.
     * This returns all statuses (including cancelled and completed) to be displayed
     * in the mobile app's Records screen.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function appointments(Request $request)
    {
        $user = $request->user();

        \Log::info("📋 Fetching appointments for user", [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'user_name' => trim(($user->first_name ?? '') . ' ' . ($user->middle_name ?? '') . ' ' . ($user->last_name ?? '')),
            'user_phone' => $user->phone_number
        ]);

        // STRICT SECURITY: ONLY get appointments directly linked to this user_id
        // NO FALLBACK MATCHING to prevent cross-user data leakage when users share phone numbers
        $appointments = Appointment::with(['clinic', 'doctor'])
            ->where('user_id', $user->id)
            ->orderBy('appointment_date', 'desc')
            ->orderBy('appointment_time', 'desc')
            ->get();
            
        \Log::info("🎯 STRICT MODE: Found {$appointments->count()} appointments with user_id={$user->id}");

        // Since we're ONLY getting appointments by user_id, no additional filtering needed
        // All appointments returned are guaranteed to belong to this user
        $filteredAppointments = $appointments;

        // Normalize output so mobile app receives expected fields
        $data = $filteredAppointments->map(function ($apt) {
            // If the appointment is completed or cancelled, use updated_at as the completion/cancellation time
            $completedAt = null;
            if (in_array($apt->status, ['completed', 'cancelled'])) {
                $completedAt = $apt->updated_at ? $apt->updated_at->format('Y-m-d H:i:s') : null;
            }

            return [
                'id' => $apt->id,
                'clinic_id' => $apt->clinic_id,
                'clinic_name' => $apt->clinic->clinic_name ?? null,
                'owner_name' => $apt->owner_name,
                'owner_phone' => $apt->owner_phone,
                'appointment_date' => $apt->appointment_date ? $apt->appointment_date->format('Y-m-d') : null,
                'appointment_time' => $apt->appointment_time,
                'status' => $apt->status,
                'created_at' => $apt->created_at ? $apt->created_at->format('Y-m-d H:i:s') : null,
                'updated_at' => $apt->updated_at ? $apt->updated_at->format('Y-m-d H:i:s') : null,
                'completed_at' => $completedAt,
                'doctor' => $apt->doctor ? [
                    'id' => $apt->doctor->id,
                    'name' => trim(($apt->doctor->first_name ?? '') . ' ' . ($apt->doctor->last_name ?? '')),
                ] : null,
            ];
        });

        \Log::info("✅ Returning {$data->count()} appointments for user {$user->id}", [
            'appointment_ids' => $data->pluck('id')->toArray(),
            'appointment_owners' => $data->pluck('owner_name')->toArray()
        ]);

        return response()->json(['appointments' => $data]);
    }

    /**
     * Return a single appointment's detailed info if it belongs to the authenticated user.
     * This includes clinic, doctor and custom responses.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function appointmentDetails(Request $request, $id)
    {
        $user = $request->user();
        $phone = $user->phone_number ?? null;
        $fullName = trim(($user->first_name ?? '') . ' ' . ($user->middle_name ?? '') . ' ' . ($user->last_name ?? ''));

        $appointment = Appointment::with(['clinic', 'doctor', 'customValues.field'])->findOrFail($id);

        // STRICT SECURITY CHECK: Only allow access if user_id matches exactly
        // NO FALLBACK to prevent cross-user data access when phone numbers are shared
        if (!$appointment->user_id || $appointment->user_id != $user->id) {
            \Log::warning("Unauthorized appointment access attempt", [
                'user_id' => $user->id,
                'appointment_id' => $id,
                'appointment_user_id' => $appointment->user_id,
                'appointment_phone' => $appointment->owner_phone,
                'appointment_name' => $appointment->owner_name
            ]);
            return response()->json(['message' => 'Not found'], 404);
        }

        $completedAt = null;
        if (in_array($appointment->status, ['completed', 'cancelled'])) {
            $completedAt = $appointment->updated_at ? $appointment->updated_at->format('Y-m-d H:i:s') : null;
        }

        // Parse consultation notes if they exist
        $consultationNotes = null;
        if ($appointment->consultation_notes) {
            // If consultation_notes is JSON, extract readable content
            $notesData = json_decode($appointment->consultation_notes, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($notesData)) {
                // Combine all consultation note fields into a readable format
                $notesParts = [];
                if (!empty($notesData['chief_complaint'])) {
                    $notesParts[] = "Chief Complaint: " . $notesData['chief_complaint'];
                }
                if (!empty($notesData['history_observations'])) {
                    $notesParts[] = "History & Observations: " . $notesData['history_observations'];
                }
                if (!empty($notesData['examination_findings'])) {
                    $notesParts[] = "Examination Findings: " . $notesData['examination_findings'];
                }
                if (!empty($notesData['diagnosis'])) {
                    $notesParts[] = "Diagnosis: " . $notesData['diagnosis'];
                }
                if (!empty($notesData['plan_recommendations'])) {
                    $notesParts[] = "Treatment Plan & Recommendations: " . $notesData['plan_recommendations'];
                }
                $consultationNotes = implode("\n\n", $notesParts);
            } else {
                // If it's not JSON, use as plain text
                $consultationNotes = $appointment->consultation_notes;
            }
        }

        $data = [
            'id' => $appointment->id,
            'clinic_id' => $appointment->clinic_id,
            'clinic_name' => $appointment->clinic->clinic_name ?? null,
            'owner_name' => $appointment->owner_name,
            'owner_phone' => $appointment->owner_phone,
            'appointment_date' => $appointment->appointment_date ? $appointment->appointment_date->format('Y-m-d') : null,
            'appointment_time' => $appointment->appointment_time,
            'status' => $appointment->status,
            'consultation_notes' => $consultationNotes,
            'doctor' => $appointment->doctor ? [
                'id' => $appointment->doctor->id,
                'name' => ($appointment->doctor->first_name ?? '') . ' ' . ($appointment->doctor->last_name ?? ''),
            ] : null,
            'responses' => $appointment->customValues->map(function ($v) {
                return [
                    'field_id' => $v->clinic_field_id,
                    'label' => $v->field->label ?? null,
                    'value' => $v->value,
                ];
            }),
            'created_at' => $appointment->created_at ? $appointment->created_at->format('Y-m-d H:i:s') : null,
            'updated_at' => $appointment->updated_at ? $appointment->updated_at->format('Y-m-d H:i:s') : null,
            'completed_at' => $completedAt,
        ];

        return response()->json(['data' => $data]);
    }

    /**
     * Get appointment receipt for mobile users
     * 
     * @param Request $request
     * @param int $appointmentId
     * @return \Illuminate\Http\JsonResponse
     */
    public function appointmentReceipt(Request $request, $appointmentId)
    {
        /** @var User $user */
        $user = $request->user();

        // Find appointment belonging to the user
        $appointment = Appointment::where('user_id', $user->id)
            ->where('id', $appointmentId)
            ->with(['clinic', 'doctor', 'customValues.field'])
            ->first();

        if (!$appointment) {
            return response()->json([
                'message' => 'Appointment not found or you do not have permission to view this receipt.'
            ], 404);
        }

        // Check if appointment has a payment receipt
        $receipt = \App\Models\PaymentReceipt::where('appointment_id', $appointment->id)->first();

        if (!$receipt) {
            return response()->json([
                'message' => 'No payment receipt found for this appointment.',
                'has_receipt' => false,
                'payment_status' => $appointment->payment_status ?? 'unpaid'
            ], 404);
        }

        // Prepare receipt data for mobile
        $receiptData = [
            'id' => $receipt->id,
            'receipt_number' => $receipt->receipt_number,
            'appointment_id' => $appointment->id,
            'patient_name' => $receipt->patient_name,
            'service_description' => $receipt->service_description,
            'amount' => floatval($receipt->amount),
            'payment_method' => $receipt->payment_method,
            'payment_date' => $receipt->payment_date ? $receipt->payment_date->format('Y-m-d H:i:s') : null,
            'notes' => $receipt->notes,
            'processed_by' => $receipt->processed_by,
            'doctor_name' => $receipt->doctor_name,
            
            // Clinic information
            'clinic' => [
                'name' => $appointment->clinic->clinic_name ?? 'N/A',
                'address' => $appointment->clinic->address ?? 'N/A',
                'contact_number' => $appointment->clinic->contact_number ?? 'N/A',
                'email' => $appointment->clinic->email ?? 'N/A',
            ],
            
            // Appointment details
            'appointment' => [
                'date' => $appointment->appointment_date,
                'time' => $appointment->appointment_time,
                'status' => $appointment->status,
                'owner_phone' => $appointment->owner_phone,
            ],
            
            // Custom field values (service details)
            'service_details' => $appointment->customValues->map(function ($value) {
                return [
                    'field' => $value->field->label ?? 'Field',
                    'value' => is_array($value->value) ? implode(', ', $value->value) : $value->value
                ];
            }),
            
            // Formatted display data for mobile UI
            'formatted' => [
                'amount_display' => '₱' . number_format($receipt->amount, 2),
                'payment_date_display' => $receipt->payment_date ? $receipt->payment_date->format('F d, Y h:i A') : 'N/A',
                'appointment_date_display' => $appointment->appointment_date ? \Carbon\Carbon::parse($appointment->appointment_date)->format('F d, Y') : 'N/A',
                'appointment_time_display' => $appointment->appointment_time ? \Carbon\Carbon::parse($appointment->appointment_time)->format('h:i A') : 'N/A',
                'payment_method_display' => ucfirst(str_replace('_', ' ', $receipt->payment_method)),
            ],
            
            'has_receipt' => true,
            'created_at' => $receipt->created_at ? $receipt->created_at->format('Y-m-d H:i:s') : null,
            'updated_at' => $receipt->updated_at ? $receipt->updated_at->format('Y-m-d H:i:s') : null,
        ];

        return response()->json([
            'data' => $receiptData,
            'message' => 'Receipt retrieved successfully'
        ]);
    }

    /**
     * Get user statistics for dashboard
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function stats(Request $request)
    {
        /** @var User $user */
        $user = $request->user();

        // Get user's appointments count
        $totalAppointments = Appointment::where('user_id', $user->id)->count();
        
        // Get upcoming appointments (future appointments)
        $upcomingAppointments = Appointment::where('user_id', $user->id)
            ->where('appointment_date', '>=', now()->toDateString())
            ->where('status', '!=', 'cancelled')
            ->count();
        
        // Get completed appointments
        $completedAppointments = Appointment::where('user_id', $user->id)
            ->where('status', 'completed')
            ->count();
            
        // Get cancelled appointments
        $cancelledAppointments = Appointment::where('user_id', $user->id)
            ->where('status', 'cancelled')
            ->count();

        // Get total pets (if pets table exists, otherwise set to 0)
        $totalPets = 0;
        try {
            // Check if pets table exists and has user relation
            if (\Schema::hasTable('pets')) {
                $totalPets = \DB::table('pets')->where('user_id', $user->id)->count();
            }
        } catch (\Exception $e) {
            // If pets table doesn't exist or has issues, default to 0
            $totalPets = 0;
        }

        return response()->json([
            'total_appointments' => $totalAppointments,
            'upcoming_appointments' => $upcomingAppointments,
            'completed_appointments' => $completedAppointments,
            'cancelled_appointments' => $cancelledAppointments,
            'total_pets' => $totalPets,
            'user_since' => $user->created_at ? $user->created_at->format('Y-m-d') : null,
        ]);
    }
}