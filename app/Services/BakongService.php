<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Label\LabelAlignment;
use Endroid\QrCode\Color\Color;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Services\BakongService;
use Endroid\QrCode\Builder\Builder;

class BakongService
{
    protected $baseUrl;
    protected $token;
    protected $accountId;
    protected $merchantName;
    protected $merchantCity;
    protected $currency;
    protected $isTest;

    public function __construct()
    {
        $this->token = config('services.bakong.token');
        $this->accountId = config('services.bakong.account_id');
        $this->merchantName = config('services.bakong.merchant_name');
        $this->merchantCity = config('services.bakong.merchant_city');
        $this->currency = config('services.bakong.currency');
        $this->isTest = config('services.bakong.is_test');
        // Use the correct Bakong API URL based on test mode
        $this->baseUrl = config('services.bakong.base_url', $this->isTest
            ? 'https://api-bakong.nbc.gov.kh'  // Test environment
            : 'https://api-bakong.nbc.gov.kh'); // Production environment - update as needed
    }

    /**
     * Generate KHQR code for an order
     */
    public function generateQR($order)
    {
        // Use the KHQR library to generate the QR code locally instead of calling API
        try {
            // Create IndividualInfo object with proper values
            // Format amount to ensure it's properly formatted for KHQR library
            $formattedAmount = number_format($order->total_amount, 2, '.', '');
            
            // Convert amount based on currency - for KHR (116) and USD (840), convert to smallest unit
            if ($this->currency == 116 || $this->currency == 840) { // KHR or USD
                // Convert to smallest currency unit (cents for USD, sen for KHR)
                $khqrAmount = (int)round($order->total_amount * 100);
            } else {
                // For other currencies, use the formatted string
                $khqrAmount = $formattedAmount;
            }
            
            $individualInfo = new \KHQR\Models\IndividualInfo(
                $this->accountId,                             // accountID
                $this->merchantName,                          // merchantName
                $this->merchantCity,                          // merchantCity
                null,                                         // acquiringBank (optional)
                null,                                         // accountInformation (optional)
                $this->currency,                              // currency
                $khqrAmount,                                  // amount - converted to smallest unit for KHQR library
                $order->order_number ?? 'ORD-' . $order->id, // billNumber
                'Canteen',                                    // storeLabel
                'Online',                                     // terminalLabel
                null,                                         // mobileNumber (optional)
                'Food Order Payment',                         // purposeOfTransaction
                null,                                         // languagePreference (optional)
                null,                                         // merchantNameAlternateLanguage (optional)
                null,                                         // merchantCityAlternateLanguage (optional)
                null                                          // upiMerchantAccount (optional)
            );

            // Create an instance of BakongKHQR to call the method
            $bakongKHQR = new \KHQR\BakongKHQR($this->token);
            $khqrResponse = $bakongKHQR->generateIndividual($individualInfo);
            
            // Extract the QR string and hash from the response
            // The data property can be an array or object, so handle both cases
            if (is_array($khqrResponse->data)) {
                $khqrString = $khqrResponse->data['qr'] ?? null;
                $khqrHash = $khqrResponse->data['md5'] ?? null;
            } else {
                $khqrString = $khqrResponse->data->qr ?? null;
                $khqrHash = $khqrResponse->data->md5 ?? null;
            }
            
            // Generate QR code image
            $qrCodeData = $this->generateLocalQRCodeFromText($khqrString);
            
            return [
                'success' => true,
                'data' => [
                    'qr' => $khqrString,
                    'md5' => $khqrHash
                ],
                'qr_string' => $qrCodeData
            ];
        } catch (\Exception $e) {
            Log::error('Bakong QR Generation Error: ' . $e->getMessage());
            
            // Fallback to manual generation
            $payload = [
                "accountId" => $this->accountId,
                "merchantName" => $this->merchantName,
                "merchantCity" => $this->merchantCity,
                "currency" => $this->currency,
                "amount" => number_format($order->total_amount, 2, '.', ''), // Use formatted amount for manual generation
                "billNumber" => $order->order_number ?? 'ORD-' . $order->id,
                "storeLabel" => "Canteen",
                "terminalLabel" => "Online",
                "purposeOfTransaction" => "Food Order Payment"
            ];
            
            $khqrString = $this->generateKHQRString($payload);
            $qrString = $this->generateLocalQRCodeFromText($khqrString);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'qr_string' => $qrString, // Provide fallback QR code
                'data' => ['fallback' => true]
            ];
        }
    }
    
    /**
     * Generate a local QR code from text content
     */
    public function generateLocalQRCodeFromText($text)
    {
        try {
            if (!$text) {
                return null;
            }
            
            // Create QR code from text using the correct API for version 6.1.3
            $builder = new Builder(
                writer: new PngWriter(),
                data: $text,
                encoding: new Encoding('UTF-8'),
                errorCorrectionLevel: ErrorCorrectionLevel::High,
                size: 300,
                margin: 10,
                foregroundColor: new Color(0, 0, 0),
                backgroundColor: new Color(255, 255, 255)
            );

            $result = $builder->build();
            
            return base64_encode($result->getString());
        } catch (\Exception $e) {
            Log::error('Local QR Code From Text Generation Error: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Generate a local QR code as fallback
     */
    public function generateLocalQRCode($data)
    {
        try {
            // Create a string representation of the payment data
            $qrContent = json_encode($data);
            
            // Create QR code using the correct API for version 6.1.3
            $builder = new Builder(
                writer: new PngWriter(),
                data: $qrContent,
                encoding: new Encoding('UTF-8'),
                errorCorrectionLevel: ErrorCorrectionLevel::High,
                size: 300,
                margin: 10,
                foregroundColor: new Color(0, 0, 0),
                backgroundColor: new Color(255, 255, 255)
            );

            $result = $builder->build();
            
            return base64_encode($result->getString());
        } catch (\Exception $e) {
            Log::error('Local QR Code Generation Error: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Save QR code to storage and return URL
     */
    public function saveQRCode($qrString, $filename = null)
    {
        if (!$filename) {
            $filename = 'qr_codes/' . Str::random(40) . '.png';
        }
        
        try {
            // The qrString is already a base64 encoded image, so we need to decode it and save directly
            $imageData = base64_decode($qrString);
            
            if ($imageData === false) {
                Log::error('Failed to decode QR string for saving');
                return null;
            }
            
            Storage::put($filename, $imageData);
            
            return Storage::url($filename);
        } catch (\Exception $e) {
            Log::error('QR Code Save Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Check payment status for an order
     */
    public function checkPayment($order)
    {
        // This method should not be used for checking individual payments by bill number
        // Instead, we should check by the MD5 hash of the QR code
        // For now, return an error indicating this method is not properly implemented
        return [
            'success' => false,
            'error' => 'checkPayment method is deprecated, use checkPaymentStatus with transaction_ref instead'
        ];
    }

    /**
     * Verify payment by transaction ID
     */
    public function verifyPayment($transactionId)
    {
        // Use the library to verify the transaction by MD5 hash
        try {
            $bakongKHQR = new \KHQR\BakongKHQR($this->token);
            $response = $bakongKHQR->checkTransactionByMD5($transactionId, $this->isTest);
            
            // Check if the response indicates success
            if (isset($response['data']) && $response['data'] !== null) {
                return [
                    'success' => true,
                    'data' => $response,
                    'status' => $response['data']['status'] ?? 'unknown'
                ];
            } else {
                return [
                    'success' => false,
                    'error' => $response['message'] ?? 'Payment not found or not completed',
                    'data' => $response
                ];
            }
        } catch (\Exception $e) {
            Log::error('Bakong Payment Verification Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Create payment and return QR code
     */
    public function createPayment($amount, $description, $reference, $callbackUrl = null, $returnUrl = null)
    {
        // For now, we'll use the generateQR method with a temporary order object
        // In a real implementation, you might need to create a temporary order first
        // Format the amount to ensure it's properly formatted for KHQR library
        $formattedAmount = number_format($amount, 2, '.', '');
        $tempOrder = (object) [
            'total_amount' => floatval($formattedAmount),
            'order_number' => $reference,
            'id' => time() // temporary ID
        ];

        $result = $this->generateQR($tempOrder);

        if ($result['success']) {
            return [
                'success' => true,
                'transaction_id' => $result['data']['data']['transactionId'] ?? null,
                'payment_url' => null, // Bakong doesn't typically return a payment URL
                'data' => $result['data'],
                'qr_string' => $result['qr_string']
            ];
        } else {
            return [
                'success' => false,
                'error' => $result['error']
            ];
        }
    }
    
    /**
     * Generate a proper KHQR code string based on payment data
     */
    public function generateKHQRString($data)
    {
        // This is a simplified version of KHQR format
        // In a real implementation, you would follow the official KHQR specification
        $qrData = [
            '00' => '01', // QR Type
            '01' => '11', // Payment Network
            '29' => [ // Merchant Account Information
                '00' => 'KH', // Country Code
                '01' => $data['accountId'], // Account ID
                '02' => $data['merchantName'], // Merchant Name
                '03' => $data['merchantCity'], // Merchant City
                '04' => $data['currency'], // Currency
                '05' => number_format($data['amount'], 2, '.', ''), // Amount
                '06' => $data['billNumber'], // Bill Number
                '07' => $data['storeLabel'], // Store Label
                '08' => $data['terminalLabel'], // Terminal Label
                '09' => $data['purposeOfTransaction'], // Purpose
            ]
        ];
        
        // Convert to proper format
        $qrString = '';
        foreach ($qrData as $key => $value) {
            if (is_array($value)) {
                $subString = '';
                foreach ($value as $subKey => $subValue) {
                    $subString .= sprintf('%02d%02d%s', $key, strlen($subKey), $subKey);
                    $subString .= sprintf('%02d%s', strlen($subValue), $subValue);
                }
                $qrString .= $subString;
            } else {
                $qrString .= sprintf('%02d%02d%s', $key, strlen($value), $value);
            }
        }
        
        // Add CRC (simplified)
        $qrString .= '6304';
        
        return $qrString;
    }

    /**
     * Handle callback from Bakong
     */
    public function handleCallback($callbackData)
    {
        // Validate callback data
        if (!isset($callbackData['billNumber']) && !isset($callbackData['reference'])) {
            return [
                'success' => false,
                'error' => 'Missing reference in callback data'
            ];
        }

        $reference = $callbackData['billNumber'] ?? $callbackData['reference'] ?? null;
        $status = $callbackData['status'] ?? 'pending';

        return [
            'success' => true,
            'reference' => $reference,
            'status' => $status,
            'transaction_id' => $callbackData['transactionId'] ?? null,
            'verification_data' => $callbackData
        ];
    }
}