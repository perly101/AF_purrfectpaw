<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Gallery;
use App\Models\ClinicInfo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ClinicGalleryController extends Controller
{
    public function index(ClinicInfo $clinic)
    {
        $images = Gallery::where('clinic_id', $clinic->id)
            ->orderByDesc('id')
            ->get()
            ->map(function ($g) {
                return [
                    'id' => $g->id,
                    'image_path' => $g->image_path,
                    'image_url' => $g->image_path ? Storage::url($g->image_path) : null,
                    'created_at' => $g->created_at,
                ];
            });

        return response()->json(['data' => $images]);
    }
}