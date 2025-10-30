<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    // Fetch logged-in user
    public function getUser(Request $request)
    {
        $user = $request->user();
        return response()->json($this->getUserData($user));
    }
    
    /**
     * Helper method to format user data consistently
     */
    private function getUserData($user)
    {
        return [
            'id'           => $user->id,
            'first_name'   => $user->first_name,
            'middle_name'  => $user->middle_name,
            'last_name'    => $user->last_name,
            'email'        => $user->email,
            'phone_number' => $user->phone_number,
            'gender'       => $user->gender,
            'birthday'     => $user->birthday,
            'role'         => $user->role,
        ];
    }

    // Update profile
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'first_name'  => 'sometimes|required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name'   => 'sometimes|required|string|max:255',
            'email'       => 'required|email|unique:users,email,' . $user->id,
            'phone_number' => 'nullable|string|max:20',
            'gender'      => 'nullable|in:female,male,prefer_not_say',
            'birthday'    => 'nullable|date',
            'password'    => 'nullable|string|min:6|confirmed',
        ]);

        if ($request->has('first_name')) {
            $user->first_name = $request->first_name;
        }
        
        if ($request->has('middle_name')) {
            $user->middle_name = $request->middle_name;
        }
        
        if ($request->has('last_name')) {
            $user->last_name = $request->last_name;
        }
        
        $user->email = $request->email;
        
        if ($request->has('phone_number')) {
            $user->phone_number = $request->phone_number;
        }
        
        if ($request->has('gender')) {
            $user->gender = $request->gender;
        }
        
        if ($request->has('birthday')) {
            $user->birthday = $request->birthday;
        }

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        return response()->json([
            'message' => 'Profile updated successfully',
            'user'    => $this->getUserData($user)
        ]);
    }
}
