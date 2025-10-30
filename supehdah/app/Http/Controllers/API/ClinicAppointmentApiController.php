<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ClinicInfo;
use App\Models\ClinicField;
use Illuminate\Http\Request;

class ClinicAppointmentApiController extends Controller
{
    public function store(Request $request, ClinicInfo $clinic)
    {
        $request->validate([
            'owner_name' => 'required|string|max:255',
            'owner_phone' => 'required|string|max:30',
            'responses' => 'required|array',
            'responses.*.field_id' => 'required|integer|exists:clinic_fields,id',
            'responses.*.value' => 'nullable',
        ]);

        // Only allow fields that belong to this clinic
        $allowedFieldIds = ClinicField::where('clinic_id', $clinic->id)->pluck('id')->all();
        foreach ($request->input('responses', []) as $r) {
            if (!in_array($r['field_id'], $allowedFieldIds, true)) {
                return response()->json(['message' => 'Invalid field for this clinic'], 422);
            }
        }

        // TODO: Persist appointment record(s) as needed
        return response()->json([
            'message' => 'Appointment created',
            'data' => [
                'clinic_id' => $clinic->id,
                'owner_name' => $request->owner_name,
                'owner_phone' => $request->owner_phone,
                'responses' => $request->responses,
            ],
        ], 201);
    }
}