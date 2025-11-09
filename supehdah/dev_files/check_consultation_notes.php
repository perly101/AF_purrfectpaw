<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

try {
    echo "Checking appointments table structure...\n";
    
    // Get all columns in appointments table
    $columns = Schema::getColumnListing('appointments');
    
    echo "All columns in appointments table:\n";
    foreach ($columns as $column) {
        echo "- $column\n";
    }
    
    // Check if consultation_notes exists
    if (in_array('consultation_notes', $columns)) {
        echo "\n✅ consultation_notes column EXISTS!\n";
        
        // Check if there are any appointments with consultation notes
        $appointmentsWithNotes = DB::table('appointments')
            ->whereNotNull('consultation_notes')
            ->where('consultation_notes', '!=', '')
            ->count();
            
        echo "Appointments with consultation notes: $appointmentsWithNotes\n";
        
        // Show sample consultation notes
        $sample = DB::table('appointments')
            ->whereNotNull('consultation_notes')
            ->where('consultation_notes', '!=', '')
            ->first();
            
        if ($sample) {
            echo "\nSample consultation notes from appointment ID {$sample->id}:\n";
            echo "\"" . substr($sample->consultation_notes, 0, 200) . (strlen($sample->consultation_notes) > 200 ? '...' : '') . "\"\n";
        }
        
    } else {
        echo "\n❌ consultation_notes column DOES NOT EXIST\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}