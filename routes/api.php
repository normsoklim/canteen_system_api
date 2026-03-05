
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\MenuItemController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\OrderDetailController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\CustomerPaymentController;
use App\Http\Controllers\Api\CustomPaymentController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\SocialAuthController;

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Social authentication routes
Route::prefix('auth')->group(function () {
    Route::get('/google', [SocialAuthController::class, 'redirectGoogle']);
    Route::get('/google/callback', [SocialAuthController::class, 'googleCallback']);
    Route::get('/config-info', [SocialAuthController::class, 'getConfigInfo']);

    Route::get('/facebook', [SocialAuthController::class, 'redirectFacebook']);
    Route::get('/facebook/callback', [SocialAuthController::class, 'facebookCallback']);
});

// Protected routes
Route::middleware(['auth:api'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/profile', [AuthController::class, 'me']);
    Route::get('/me', [AuthController::class, 'me']);
});

Route::middleware(['auth:api', 'role:admin'])->group(function () {
    // Protected routes for admin and staff
    Route::get('/users', [AuthController::class, 'index']);
    Route::apiResource('categories', CategoryController::class);
    Route::apiResource('menu-items', MenuItemController::class);
    Route::apiResource('orders', OrderController::class);

    // Report routes
    Route::prefix('reports')->group(function () {
        Route::get('/dashboard', [ReportController::class, 'dashboard']);
        Route::get('/profit', [ReportController::class, 'profit']);
        Route::get('/category', [ReportController::class, 'category']);
        Route::get('/hourly', [ReportController::class, 'hourly']);
        Route::get('/staff', [ReportController::class, 'staff']);
        Route::get('/export-excel', [ReportController::class, 'exportExcel']);
        Route::get('/export-pdf', [ReportController::class, 'exportPdf']);
    });
});

Route::middleware(['auth:api', 'role:customer'])->group(function () {
    // Protected routes for customers
    Route::get('/menu-items', [MenuItemController::class, 'index']);
    Route::get('/menu-items/{id}', [MenuItemController::class, 'show']);
    Route::get('/orders', [OrderController::class, 'index']);
    Route::post('/orders-with-items', [OrderController::class, 'storeWithItems']);
    Route::get('/orders/{id}', [OrderController::class, 'show']);
    Route::apiResource('order-details', OrderDetailController::class);
    Route::apiResource('payments', PaymentController::class);
    
    // Customer payment history and payment routes
    Route::get('/customer/payment-history', [CustomerPaymentController::class, 'getPaymentHistory']);
    Route::post('/customer/orders/{orderId}/initiate-bakong-payment', [CustomerPaymentController::class, 'initiateBakongPaymentForOrder']);
    Route::get('/customer/payments/{paymentId}/verify-bakong', [CustomerPaymentController::class, 'verifyBakongPaymentForOrder']);
    Route::get('/customer/orders/{orderId}', [CustomerPaymentController::class, 'getOrderDetails']);
    
    // User payment and order history routes
    Route::get('/user/payment-history', [PaymentController::class, 'getUserPaymentHistory']);
    Route::get('/user/order-history', [PaymentController::class, 'getUserOrderHistory']);
    Route::get('/user/orders/{id}', [PaymentController::class, 'getUserOrder']);
    
    // Bakong payment routes
    Route::post('/payments/bakong/initiate', [PaymentController::class, 'initiateBakongPayment']);
    Route::post('/payments/bakong/initiate-custom', [CustomPaymentController::class, 'initiateBakongPayment']);
    Route::get('/payments/{id}/verify-bakong', [PaymentController::class, 'verifyBakongPayment']);
    Route::post('/payment/callback', [PaymentController::class, 'handleBakongCallback']);
    Route::get('/checkout/{id}', [PaymentController::class, 'checkout']);
});
