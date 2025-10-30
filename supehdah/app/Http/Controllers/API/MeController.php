<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class MeController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'id'           => $user->id,
            'first_name'   => $user->first_name,
            'middle_name'  => $user->middle_name,
            'last_name'    => $user->last_name,
            // For backward compatibility with older clients
            'name'         => trim($user->first_name . ' ' . ($user->middle_name ? $user->middle_name . ' ' : '') . $user->last_name),
            'email'        => $user->email,
            'phone_number' => $user->phone_number,
            'gender'       => $user->gender,
            'birthday'     => $user->birthday,
            'role'         => $user->role,
        ]);
    }
}