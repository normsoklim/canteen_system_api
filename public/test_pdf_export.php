<?php
// Test script to verify PDF export functionality

require_once __DIR__ . '/../vendor/autoload.php';

use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

try {
    // Test if the PDF facade is working
    if (class_exists('Barryvdh\DomPDF\Facade\Pdf')) {
        echo "✓ PDF Facade is available\n";
    } else {
        echo "✗ PDF Facade is NOT available\n";
    }
    
    // Test if the PDF facade can be used
    $testData = [
        'orders' => [
            (object)['id' => 1, 'user_id' => 1, 'total_amount' => 100.00, 'order_status' => 'Completed', 'created_at' => '2026-01-01 10:00:00'],
            (object)['id' => 2, 'user_id' => 2, 'total_amount' => 200.00, 'order_status' => 'Completed', 'created_at' => '2026-01-01 11:00:00']
        ],
        'startDate' => '2026-01-01',
        'endDate' => '2026-01-31'
    ];
    
    // Try to create a PDF
    $pdf = Pdf::loadView('reports.sales', $testData);
    
    if ($pdf) {
        echo "✓ PDF creation successful\n";
        
        // Try to save the PDF to test if it works completely
        $pdfPath = __DIR__ . '/../storage/app/test_pdf_export.pdf';
        $pdf->save($pdfPath);
        
        if (file_exists($pdfPath)) {
            echo "✓ PDF saved successfully to: " . $pdfPath . "\n";
            echo "✓ PDF export functionality is working correctly!\n";
            
            // Clean up the test file
            unlink($pdfPath);
            echo "✓ Test file cleaned up\n";
        } else {
            echo "✗ Failed to save PDF file\n";
        }
    } else {
        echo "✗ Failed to create PDF\n";
    }
    
} catch (Exception $e) {
    echo "Error during PDF test: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

echo "PDF export test completed.\n";