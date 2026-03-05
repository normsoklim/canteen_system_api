<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Route to serve the Bakong payment UI
Route::get('/bakong-payment', function () {
    return file_get_contents(public_path('bakong-payment-ui.html'));
});

// Route to serve the Bakong QR test page
Route::get('/test-bakong-qr', function () {
    return file_get_contents(public_path('test-bakong-qr.html'));
});

// Route to serve the Bakong test guide
Route::get('/bakong-test-guide', function () {
    return file_get_contents(public_path('bakong-test-guide.html'));
});

// Route to serve the authentication helper
Route::get('/auth-helper', function () {
    return file_get_contents(public_path('auth-helper.html'));
});

// Route to serve the menu items page
Route::get('/menu-items', function () {
    return file_get_contents(public_path('menu-items.html'));
});

// Route to serve the test order creation page
Route::get('/test-order-creation', function () {
    return file_get_contents(public_path('test_order_creation.html'));
});

// Route to serve the debug order API page
Route::get('/debug-order-api', function () {
    return file_get_contents(public_path('debug-order-api.html'));
});

// Route to serve the debug payment creation page
Route::get('/debug-payment-creation', function () {
    return file_get_contents(public_path('debug-payment-creation.html'));
});
