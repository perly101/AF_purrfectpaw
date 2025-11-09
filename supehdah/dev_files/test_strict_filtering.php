<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Appointment;
use Illuminate\Http\Request;
use App\Http\Controllers\API\ProfileController;

echo "=== TESTING STRICT USER APPOINTMENT FILTERING ===\n\n";

// Test each user
$users = User::all();

foreach ($users as $user) {
    echo "=== Testing User ID: {$user->id} ===\n";
    echo "Name: {$user->first_name} {$user->last_name}\n";
    echo "Phone: {$user->phone_number}\n";
    echo "Email: {$user->email}\n";
    
    // Simulate authentication
    auth()->login($user);
    
    // Test the ProfileController appointments method
    $controller = new ProfileController();
    $request = new Request();
    $request->setUserResolver(function() use ($user) {
        return $user;
    });
    
    $response = $controller->appointments($request);
    $data = json_decode($response->getContent(), true);
    
    if (isset($data['appointments'])) {
        $appointments = $data['appointments'];
        $count = count($appointments);
        echo "Found {$count} appointments:\n";
        
        foreach ($appointments as $apt) {
            echo "  - ID: {$apt['id']} | Owner: {$apt['owner_name']} | Phone: {$apt['owner_phone']}\n";
        }
    } else {
        echo "No appointments found or error occurred\n";
        echo "Response: " . $response->getContent() . "\n";
    }
    
    echo "\n";
}

echo "=== Raw Database Check ===\n";
$appointments = Appointment::select('id', 'user_id', 'owner_name', 'owner_phone')->get();
foreach ($appointments as $apt) {
    echo "ID: {$apt->id} | user_id: " . ($apt->user_id ?: 'NULL') . " | Owner: {$apt->owner_name} | Phone: {$apt->owner_phone}\n";
}