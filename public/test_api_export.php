<?php
// Test script to verify PDF export functionality through the API

require_once __DIR__ . '/../vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

// Bootstrap Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Create a test request to the export PDF endpoint
try {
    // Create a fake request to test the export functionality
    $request = Illuminate\Http\Request::create('/api/reports/export-pdf', 'GET', [
        'start' => '2026-01-01',
        'end' => '2026-12-31'
    ]);
    
    // Set the request in the container
    $app->instance('request', $request);
    
    // Get the ReportController instance
    $controller = $app->make(App\Http\Controllers\Api\ReportController::class);
    
    // Call the exportPdf method
    $response = $controller->exportPdf($request);
    
    echo "✓ PDF export method executed successfully\n";
    echo "Response type: " . get_class($response) . "\n";
    echo "Response headers: " . print_r($response->headers->all(), true) . "\n";
    
    // Check if the response is a PDF response
    if (strpos($response->headers->get('content-type'), 'application/pdf') !== false) {
        echo "✓ PDF response has correct content type\n";
        echo "✓ PDF export functionality is working correctly!\n";
    } else {
        echo "✗ PDF response has incorrect content type\n";
    }
    
} catch (Exception $e) {
    echo "Error during API test: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

echo "API export test completed.\n";