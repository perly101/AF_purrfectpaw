<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ClinicInfo;
use App\Models\ClinicField;

class ClinicFieldApiController extends Controller
{
    public function index(ClinicInfo $clinic)
    {
        // $clinic->id is a property (no parentheses)
        $fields = ClinicField::where('clinic_id', $clinic->id)
            ->orderBy('order')
            ->get();

        return response()->json(['data' => $fields]);
    }
}