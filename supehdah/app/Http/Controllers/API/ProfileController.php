<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use App\Models\Appointment;

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

        $phone = $user->phone_number ?? null;
        $fullName = trim(($user->first_name ?? '') . ' ' . ($user->middle_name ?? '') . ' ' . ($user->last_name ?? ''));

    $query = Appointment::with(['clinic', 'doctor']);

        // Build matching conditions: prefer phone match if available, otherwise match by name
        $query->where(function ($q) use ($phone, $fullName) {
            if (!empty($phone)) {
                $q->where('owner_phone', $phone);
            }

            if (!empty($fullName)) {
                // also include partial name matches to be more forgiving
                $q->orWhere('owner_name', 'like', "%{$fullName}%");
            }
        });

        $appointments = $query->orderBy('appointment_date', 'desc')
            ->orderBy('appointment_time', 'desc')
            ->get();

        // Normalize output so mobile app receives expected fields
        $data = $appointments->map(function ($apt) {
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

        // Ensure appointment belongs to this user by phone or name match
        $belongs = false;
        if ($phone && $appointment->owner_phone === $phone) $belongs = true;
        if (!$belongs && $fullName && stripos($appointment->owner_name, $fullName) !== false) $belongs = true;

        if (!$belongs) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $completedAt = null;
        if (in_array($appointment->status, ['completed', 'cancelled'])) {
            $completedAt = $appointment->updated_at ? $appointment->updated_at->format('Y-m-d H:i:s') : null;
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
}