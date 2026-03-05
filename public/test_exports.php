<?php
// Comprehensive test script to verify export functionality

require_once __DIR__ . '/../vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

// Bootstrap Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    // Test if the PDF facade is working
    if (class_exists('Barryvdh\DomPDF\Facade\Pdf')) {
        echo "✓ PDF Facade is available\n";
    } else {
        echo "✗ PDF Facade is NOT available\n";
    }
    
    // Test if the Excel facade is working
    if (class_exists('Maatwebsite\Excel\Facades\Excel')) {
        echo "✓ Excel Facade is available\n";
    } else {
        echo "✗ Excel Facade is NOT available\n";
    }
    
    // Test if the ReportController exists
    if (class_exists('App\Http\Controllers\Api\ReportController')) {
        echo "✓ ReportController exists\n";
        
        // Test if the exportPdf method exists
        $methods = get_class_methods('App\Http\Controllers\Api\ReportController');
        if (in_array('exportPdf', $methods)) {
            echo "✓ exportPdf method exists in ReportController\n";
        } else {
            echo "✗ exportPdf method does NOT exist in ReportController\n";
        }
    } else {
        echo "✗ ReportController does NOT exist\n";
    }
    
    // Test if the SalesReportExport exists
    if (class_exists('App\Exports\SalesReportExport')) {
        echo "✓ SalesReportExport exists\n";
    } else {
        echo "✗ SalesReportExport does NOT exist\n";
    }
    
    // Test if the ReportService exists
    if (class_exists('App\Services\ReportService')) {
        echo "✓ ReportService exists\n";
    } else {
        echo "✗ ReportService does NOT exist\n";
    }
    
    // Test if the ReportRepository exists
    if (class_exists('App\Repositories\ReportRepository')) {
        echo "✓ ReportRepository exists\n";
    } else {
        echo "✗ ReportRepository does NOT exist\n";
    }
    
    // Test if the sales report view exists
    $viewPath = __DIR__ . '/../resources/views/reports/sales.blade.php';
    if (file_exists($viewPath)) {
        echo "✓ Sales report view exists\n";
    } else {
        echo "✗ Sales report view does NOT exist\n";
    }
    
    // Test the actual export functionality
    try {
        $request = Illuminate\Http\Request::create('/api/reports/export-pdf', 'GET', [
            'start' => '2026-01-01',
            'end' => '2026-12-31'
        ]);
        
        $app->instance('request', $request);
        
        $controller = $app->make(App\Http\Controllers\Api\ReportController::class);
        $response = $controller->exportPdf($request);
        
        if ($response && strpos($response->headers->get('content-type'), 'application/pdf') !== false) {
            echo "✓ PDF export functionality is working correctly!\n";
        } else {
            echo "✗ PDF export functionality is NOT working correctly\n";
        }
    } catch (Exception $e) {
        echo "✗ Error testing PDF export functionality: " . $e->getMessage() . "\n";
    }
    
    echo "Export functionality test completed.\n";
} catch (Exception $e) {
    echo "Error during test: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
?>