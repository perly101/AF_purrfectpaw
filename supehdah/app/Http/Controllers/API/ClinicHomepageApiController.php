<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ClinicInfo;
use App\Models\ClinicHomepage;
use App\Models\ClinicService;
use Illuminate\Support\Facades\Storage;

class ClinicHomepageApiController extends Controller
{
    public function show(ClinicInfo $clinic)
    {
        $homepage = ClinicHomepage::firstOrCreate(
            ['clinic_id' => $clinic->id],
            ['hero_title' => 'Welcome to ' . $clinic->clinic_name]
        );

        $services = ClinicService::where('clinic_id', $clinic->id)
            ->orderBy('order')
            ->get()
            ->map(function ($s) {
                return [
                    'id'          => $s->id,
                    'name'        => $s->name,
                    'description' => $s->description,
                    'price'       => $s->price,
                    'is_active'   => (bool) $s->is_active,
                    'image_path'  => $s->image_path,
                    'image_url'   => $s->image_path ? Storage::url($s->image_path) : null,
                ];
            });

        return response()->json([
            'clinic' => [
                'id' => $clinic->id,
                'clinic_name' => $clinic->clinic_name,
            ],
            'homepage' => [
                'hero_title'         => $homepage->hero_title,
                'hero_subtitle'      => $homepage->hero_subtitle,
                'hero_image'         => $homepage->hero_image,               // e.g. clinic/home/hero/xxx.jpg
                'about_text'         => $homepage->about_text,
                'announcement_title' => $homepage->announcement_title,
                'announcement_body'  => $homepage->announcement_body,
                'announcement_image' => $homepage->announcement_image,       // e.g. clinic/home/ann/xxx.jpg
                // optional absolute URLs if you want:
                'hero_image_url'         => $homepage->hero_image ? Storage::url($homepage->hero_image) : null,
                'announcement_image_url' => $homepage->announcement_image ? Storage::url($homepage->announcement_image) : null,
            ],
            'services' => $services,
        ]);
    }
}