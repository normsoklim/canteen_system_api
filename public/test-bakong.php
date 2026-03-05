<?php
// Simple test script to verify Bakong functionality
require_once __DIR__ . '/../vendor/autoload.php';

use Illuminate\Support\Facades\Http;

// Test the Bakong API connection
echo "Testing Bakong API connection...\n";

// Load environment variables
if (file_exists(__DIR__ . '/../.env')) {
    $env = file_get_contents(__DIR__ . '/../.env');
    $lines = explode("\n", $env);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            if (!empty($key)) {
                $_ENV[$key] = $value;
                putenv("$key=$value");
            }
        }
    }
}

// Test basic functionality
echo "BAKONG_TOKEN exists: " . (getenv('BAKONG_TOKEN') ? 'Yes' : 'No') . "\n";
echo "BAKONG_ACCOUNT_ID: " . getenv('BAKONG_ACCOUNT_ID') . "\n";
echo "BAKONG_BASE_URL: " . getenv('BAKONG_BASE_URL') . "\n";

echo "Test script completed.\n";
?>