<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Pet;
use App\Models\PetVaccination;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class PetApiController extends Controller
{
    /**
     * Get all pets for the authenticated user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $pets = $request->user()->pets()->with('vaccinations')->get();

        $pets->transform(function ($pet) {
            // Transform the image URL to a full URL
            if ($pet->image) {
                $pet->image_url = url('storage/' . $pet->image);
            } else {
                $pet->image_url = url('images/default-pet.jpg');
            }
            
            return $pet;
        });

        return response()->json([
            'status' => 'success',
            'data' => $pets
        ]);
    }

    /**
     * Store a newly created pet in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'breed' => 'required|string|max:255',
            'age' => 'required|numeric|min:0',
            'birthday' => 'required|date',
            'last_vaccination_date' => 'nullable|date',
            'vaccination_details' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->except('image');
        $data['user_id'] = $request->user()->id;

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('pet-images', 'public');
        }

        $pet = Pet::create($data);

        // Add image URL for frontend
        if ($pet->image) {
            $pet->image_url = url('storage/' . $pet->image);
        } else {
            $pet->image_url = url('images/default-pet.jpg');
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Pet added successfully!',
            'data' => $pet
        ], 201);
    }

    /**
     * Display the specified pet.
     *
     * @param  int  $id
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id, Request $request)
    {
        $pet = Pet::with('vaccinations')->find($id);

        if (!$pet) {
            return response()->json([
                'status' => 'error',
                'message' => 'Pet not found'
            ], 404);
        }

        // Check if the authenticated user owns this pet
        if ($pet->user_id !== $request->user()->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized access'
            ], 403);
        }

        // Add image URL for frontend
        if ($pet->image) {
            $pet->image_url = url('storage/' . $pet->image);
        } else {
            $pet->image_url = url('images/default-pet.jpg');
        }

        return response()->json([
            'status' => 'success',
            'data' => $pet
        ]);
    }

    /**
     * Update the specified pet in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $pet = Pet::find($id);

        if (!$pet) {
            return response()->json([
                'status' => 'error',
                'message' => 'Pet not found'
            ], 404);
        }

        // Check if the authenticated user owns this pet
        if ($pet->user_id !== $request->user()->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized access'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'breed' => 'required|string|max:255',
            'age' => 'required|numeric|min:0',
            'birthday' => 'required|date',
            'last_vaccination_date' => 'nullable|date',
            'vaccination_details' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->except('image');

        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($pet->image && Storage::disk('public')->exists($pet->image)) {
                Storage::disk('public')->delete($pet->image);
            }
            $data['image'] = $request->file('image')->store('pet-images', 'public');
        }

        $pet->update($data);

        // Add image URL for frontend
        if ($pet->image) {
            $pet->image_url = url('storage/' . $pet->image);
        } else {
            $pet->image_url = url('images/default-pet.jpg');
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Pet updated successfully!',
            'data' => $pet
        ]);
    }

    /**
     * Remove the specified pet from storage.
     *
     * @param  int  $id
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id, Request $request)
    {
        $pet = Pet::find($id);

        if (!$pet) {
            return response()->json([
                'status' => 'error',
                'message' => 'Pet not found'
            ], 404);
        }

        // Check if the authenticated user owns this pet
        if ($pet->user_id !== $request->user()->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized access'
            ], 403);
        }

        // Delete pet image if exists
        if ($pet->image && Storage::disk('public')->exists($pet->image)) {
            Storage::disk('public')->delete($pet->image);
        }

        $pet->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Pet deleted successfully!'
        ]);
    }

    /**
     * Store a new vaccination record for a pet.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeVaccination(Request $request, $id)
    {
        $pet = Pet::find($id);

        if (!$pet) {
            return response()->json([
                'status' => 'error',
                'message' => 'Pet not found'
            ], 404);
        }

        // Check if the authenticated user owns this pet
        if ($pet->user_id !== $request->user()->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized access'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'vaccine_name' => 'required|string|max:255',
            'vaccination_date' => 'required|date',
            'next_due_date' => 'nullable|date',
            'administered_by' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $vaccination = new PetVaccination($request->all());
        $pet->vaccinations()->save($vaccination);

        // Update the pet's last vaccination date if this is the most recent
        if ($pet->last_vaccination_date === null || 
            $request->vaccination_date > $pet->last_vaccination_date) {
            $pet->update(['last_vaccination_date' => $request->vaccination_date]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Vaccination record added successfully!',
            'data' => $vaccination
        ], 201);
    }

    /**
     * Get all vaccination records for a pet.
     *
     * @param  int  $id
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getVaccinations($id, Request $request)
    {
        $pet = Pet::find($id);

        if (!$pet) {
            return response()->json([
                'status' => 'error',
                'message' => 'Pet not found'
            ], 404);
        }

        // Check if the authenticated user owns this pet
        if ($pet->user_id !== $request->user()->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized access'
            ], 403);
        }

        $vaccinations = $pet->vaccinations()->orderBy('vaccination_date', 'desc')->get();

        return response()->json([
            'status' => 'success',
            'data' => $vaccinations
        ]);
    }
}
