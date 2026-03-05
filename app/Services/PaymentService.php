<?php

namespace App\Services;

use KHQR\BakongKHQR;
use KHQR\Models\IndividualInfo;
use App\Models\Payment;
use App\Models\Order;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Color\Color;

class PaymentService
{
    private $bakongToken;

    public function __construct()
    {
        $this->bakongToken = config('services.bakong.token');
    }

    /**
     * Create a Bakong KHQR payment
     */
    public function createBakongPayment($orderId, $amount, $description = null)
    {
        $order = Order::findOrFail($orderId);
        
        // Validate and format the amount
        if ($amount === null || $amount === '' || !is_numeric($amount) || $amount <= 0) {
            // If amount is not provided or invalid, use the order's total_amount
            $amount = $order->total_amount ?? 0;
        }
        
        // Ensure amount is numeric and greater than 0
        $amount = (float)$amount;
        
        // Validate that amount is greater than 0
        if ($amount <= 0) {
            throw new \Exception('Amount must be greater than 0');
        }
        
        // Format amount to 2 decimal places for display/storage
        $formattedAmount = number_format($amount, 2, '.', '');
        
        // Get merchant information from config
        $merchantInfo = [
            'bakong_account_id' => config('services.bakong.account_id'),
            'merchant_name' => config('services.bakong.merchant_name', config('app.name')),
            'merchant_city' => config('services.bakong.merchant_city', 'Phnom Penh'),
            'currency' => config('services.bakong.currency', 116), // 116 for KHR, 840 for USD
        ];

        // Convert amount based on currency - for KHR (116) and USD (840), convert to smallest unit
        $currency = $merchantInfo['currency'];
        if ($currency == 116 || $currency == 840) { // KHR or USD
            // Convert to smallest currency unit (cents for USD, sen for KHR)
            $khqrAmount = (int)round($amount * 100);
        } else {
            // For other currencies, use the formatted string
            $khqrAmount = $formattedAmount;
        }

        // Create IndividualInfo object with proper values
        $individualInfo = new IndividualInfo(
            $merchantInfo['bakong_account_id'],           // accountID
            $merchantInfo['merchant_name'],               // merchantName
            $merchantInfo['merchant_city'],               // merchantCity
            null,                                         // acquiringBank (optional)
            null,                                         // accountInformation (optional)
            $merchantInfo['currency'],                    // currency
            $khqrAmount,                                  // amount - converted to integer for KHQR library
            "ORD-{$order->id}",                          // billNumber
            'Canteen',                                    // storeLabel
            'Online',                                     // terminalLabel
            null,                                         // mobileNumber (optional)
            $description ?? "Order Payment #{$order->id}", // purposeOfTransaction
            null,                                         // languagePreference (optional)
            null,                                         // merchantNameAlternateLanguage (optional)
            null,                                         // merchantCityAlternateLanguage (optional)
            null                                          // upiMerchantAccount (optional)
        );

        // Generate KHQR code using the library
        try {
            $bakongKHQR = new \KHQR\BakongKHQR($this->bakongToken);
            $khqrResponse = $bakongKHQR->generateIndividual($individualInfo);
            
            // The response is a KHQRResponse object with data property that can be array or object
            // Check the structure and access qr and md5 properties properly
            if (is_array($khqrResponse->data)) {
                $khqrString = $khqrResponse->data['qr'] ?? null;
                $khqrHash = $khqrResponse->data['md5'] ?? null;
            } else {
                $khqrString = $khqrResponse->data->qr ?? null;
                $khqrHash = $khqrResponse->data->md5 ?? null;
            }
            
            // Log the response for debugging
            \Log::info('KHQR Generated:', [
                'khqr_string' => $khqrString,
                'khqr_hash' => $khqrHash,
                'amount' => $formattedAmount,
                'description' => $description,
                'response_type' => gettype($khqrResponse),
                'response_class' => get_class($khqrResponse),
                'data_type' => gettype($khqrResponse->data)
            ]);
        } catch (\Exception $e) {
            \Log::error('KHQR Generation Error: ' . $e->getMessage());
            throw new \Exception('Failed to generate KHQR string: ' . $e->getMessage());
        }
        
        if (!$khqrString) {
            // If we still don't have a QR string, try to generate it manually
            \Log::warning('KHQR string not found in response, attempting to generate manually');
            
            // Generate a basic KHQR string manually based on the merchant info
            $khqrString = $this->generateKHQRString([
                'accountId' => $merchantInfo['bakong_account_id'],
                'merchantName' => $merchantInfo['merchant_name'],
                'merchantCity' => $merchantInfo['merchant_city'],
                'currency' => $merchantInfo['currency'],
                'amount' => $formattedAmount,  // Use formatted amount for manual generation
                'billNumber' => "ORD-{$order->id}",
                'storeLabel' => 'Canteen',
                'terminalLabel' => 'Online',
                'purposeOfTransaction' => $description ?? "Order Payment #{$order->id}",
            ]);
            
            // Generate a hash manually
            $khqrHash = md5($khqrString . time());
        }
        
        if (!$khqrHash) {
            $khqrHash = md5($khqrString . time()); // Generate a hash if not available
        }

         // Create payment record
         $payment = Payment::create([
             'order_id' => $orderId,
             'amount' => $formattedAmount,
             'payment_method' => 'digital',
             'payment_status' => 'pending',
             'payment_gateway' => 'bakong',
             'transaction_ref' => $khqrHash,
         ]);

        // Generate QR code image using endroid/qr-code
        $qrCode = new QrCode($khqrString);
        
        $writer = new PngWriter();
        $result = $writer->write($qrCode);
        
        $qrCodeDataUrl = base64_encode($result->getString());

        return [
            'payment' => $payment,
            'khqr_string' => $khqrString,
            'khqr_hash' => $khqrHash,
            'qr_code_image' => 'data:image/png;base64,' . $qrCodeDataUrl,
            'merchant_info' => $merchantInfo,
        ];
    }

    /**
     * Generate a proper KHQR code string based on payment data
     */
    private function generateKHQRString($data)
    {
        // Proper KHQR format following EMV QR Code Specification
        // Format: ID(2 digits) + Length(2 digits) + Value
        
        $qrString = '';
        
        // Payload Format Indicator (ID: 00)
        $qrString .= '000201'; // ID=00, Length=02, Value=01
        
        // Point of Initiation Method (ID: 01) - 12 for dynamic
        $qrString .= '010212'; // ID=01, Length=02, Value=12 (dynamic)
        
        // Merchant Account Information (ID: 29) - This is the main part
        $gui = 'A000000677010111'; // GUI for Bakong (A000000677010111)
        $account = $data['accountId'];
        $merchantInfo = $gui . '0215' . $account; // Adding account info
        
        // Add merchant name and city
        $name = $data['merchantName'];
        $city = $data['merchantCity'];
        $nameLen = strlen($name) < 100 ? strlen($name) : 99; // Max 99 chars
        $cityLen = strlen($city) < 100 ? strlen($city) : 99; // Max 99 chars
        
        $merchantInfo .= '03' . sprintf('%02d', $nameLen) . $name;
        $merchantInfo .= '04' . sprintf('%02d', $cityLen) . $city;
        
        $merchantInfoLen = strlen($merchantInfo);
        $qrString .= sprintf('29%02d%s', $merchantInfoLen, $merchantInfo);
        
        // Transaction Currency (ID: 53) - 3 digits
        $currency = str_pad($data['currency'], 3, '0', STR_PAD_LEFT);
        $qrString .= '5303' . $currency;
        
        // Transaction Amount (ID: 54) - Optional but recommended
        $amount = number_format($data['amount'], 2, '.', ''); // Format to 2 decimal places
        $amountLen = strlen($amount);
        $qrString .= sprintf('54%02d%s', $amountLen, $amount);
        
        // Tip or Convenience Indicator (ID: 55) - Not used
        // Value of Tip or Convenience Fee (ID: 56) - Not used
        
        // Country Code (ID: 58) - 2 letters
        $qrString .= '5802KH';
        
        // Merchant Name (ID: 59) - Already included in merchant account info
        // Merchant City (ID: 60) - Already included in merchant account info
        
        // Additional Data Field Template (ID: 62) - For reference numbers
        $reference = $data['billNumber'];
        $refLen = strlen($reference) < 100 ? strlen($reference) : 99;
        $additionalData = '05' . sprintf('%02d', $refLen) . $reference; // Reference label
        $additionalDataLen = strlen($additionalData);
        $qrString .= sprintf('62%02d%s', $additionalDataLen, $additionalData);
        
        // Purpose of Transaction (ID: 64) - Using sub-ID 06 for purpose
        $purpose = $data['purposeOfTransaction'];
        $purposeLen = strlen($purpose) < 100 ? strlen($purpose) : 99;
        $purposeData = '06' . sprintf('%02d', $purposeLen) . $purpose;
        $purposeDataLen = strlen($purposeData);
        $qrString .= sprintf('64%02d%s', $purposeDataLen, $purposeData);
        
        // Calculate CRC (ID: 63) - 4 characters
        // The CRC covers all characters from the start of the QR code to this point
        $dataForCRC = $qrString;
        $crc = $this->calculateCRC($dataForCRC);
        $qrString .= '6304' . $crc;
        
        return $qrString;
    }
    
    /**
     * Calculate CRC for KHQR code following ISO 13239 / HDLC
     */
    private function calculateCRC($data)
    {
        // CRC-16/CCITT-FALSE algorithm (0xFFFF initial, 0x1021 polynomial, no final XOR)
        $crc = 0xFFFF;
        $length = strlen($data);
        
        for ($i = 0; $i < $length; $i++) {
            $crc ^= ord($data[$i]) << 8;
            for ($j = 0; $j < 8; $j++) {
                if ($crc & 0x8000) {
                    $crc = ($crc << 1) ^ 0x1021;
                } else {
                    $crc <<= 1;
                }
                $crc &= 0xFFFF; // Keep it within 16-bit
            }
        }
        
        // Convert to 4-character hex string
        $crcHex = strtoupper(dechex($crc));
        return str_pad($crcHex, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Verify a Bakong payment
     */
    public function verifyBakongPayment($khqrHash, $isTest = false)
    {
        try {
            if (empty($khqrHash)) {
                \Log::warning('Empty khqrHash provided for verification');
                return null;
            }
            
            // Create an instance of BakongKHQR to call the method
            $bakongKHQR = new \KHQR\BakongKHQR($this->bakongToken);
            $response = $bakongKHQR->checkTransactionByMD5($khqrHash, $isTest);
            \Log::info('Bakong verification response: ' . json_encode($response));
            return $response;
        } catch (\Exception $e) {
            \Log::error('Bakong payment verification failed: ' . $e->getMessage());
            \Log::error('Exception trace: ' . $e->getTraceAsString());
            return null;
        }
    }

    /**
     * Check if a payment has been made
     */
    public function checkPaymentStatus($paymentId)
    {
        try {
            $payment = Payment::findOrFail($paymentId);
            
            if ($payment->payment_status === 'paid') {
                return [
                    'status' => 'paid',
                    'payment' => $payment
                ];
            }

            $verification = $this->verifyBakongPayment($payment->transaction_ref);
            
            if ($verification && isset($verification['data'])) {
                $transaction = $verification['data'];
                
                // Update payment status if transaction is confirmed
                if (isset($transaction['status']) && $transaction['status'] === 'SUCCESS') {
                    $payment->update([
                        'payment_status' => 'paid',
                        'payment_date' => now()
                    ]);
                    
                    // Update order status if order exists
                    if ($payment->order) {
                        $payment->order->update(['payment_status' => 'paid', 'order_status' => 'confirmed']);
                    }
                    
                    return [
                        'status' => 'paid',
                        'payment' => $payment
                    ];
                }
            }
            
            return [
                'status' => 'pending',
                'payment' => $payment
            ];
        } catch (\Exception $e) {
            \Log::error('Error in checkPaymentStatus: ' . $e->getMessage());
            // Return payment with current status if there's an error
            $payment = Payment::find($paymentId);
            return [
                'status' => $payment ? $payment->payment_status : 'error',
                'payment' => $payment
            ];
        }
    }
}