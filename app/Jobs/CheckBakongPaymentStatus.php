<?php

namespace App\Jobs;

use App\Models\Payment;
use App\Services\BakongService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckBakongPaymentStatus implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $payment;

    /**
     * Create a new job instance.
     */
    public function __construct(Payment $payment)
    {
        $this->payment = $payment;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $bakongService = new BakongService();
        
        // Check if payment has expired
        if ($this->payment->expires_at && now()->gt($this->payment->expires_at)) {
            // Mark payment as expired
            $this->payment->update([
                'payment_status' => 'failed',
                'gateway_response' => array_merge(
                    (array)$this->payment->gateway_response,
                    ['status' => 'expired', 'message' => 'Payment expired']
                )
            ]);
            
            // Update order status
            if ($this->payment->order) {
                $this->payment->order->update([
                    'payment_status' => 'unpaid'
                ]);
            }
            
            Log::info("Bakong payment {$this->payment->id} has expired");
            return;
        }

        // Check payment status with Bakong
        $result = $bakongService->checkPayment($this->payment->order);
        
        if ($result['success']) {
            $status = $result['status'];
            
            // Update payment status based on response
            $newStatus = match($status) {
                'SUCCESS', 'COMPLETED', 'completed' => 'paid',
                'FAILED', 'failed' => 'failed',
                default => $this->payment->payment_status // Keep current status if still pending
            };
            
            $this->payment->update([
                'payment_status' => $newStatus,
                'gateway_response' => array_merge(
                    (array)$this->payment->gateway_response,
                    $result['data']
                )
            ]);
            
            // Update order status if payment status changed
            if ($this->payment->order) {
                $orderStatus = match($newStatus) {
                    'paid' => 'paid',
                    'failed' => 'unpaid',
                    default => $this->payment->order->payment_status
                };
                
                $this->payment->order->update([
                    'payment_status' => $orderStatus
                ]);
            }
            
            // If payment is still pending and not expired, reschedule the job
            if ($newStatus === 'pending' && (!$this->payment->expires_at || now()->lt($this->payment->expires_at))) {
                // Reschedule to check again in 30 seconds
                CheckBakongPaymentStatus::dispatch($this->payment)->delay(now()->addSeconds(30));
            } else {
                Log::info("Bakong payment {$this->payment->id} status updated to: {$newStatus}");
            }
        } else {
            Log::error("Failed to check Bakong payment status for payment {$this->payment->id}", [
                'error' => $result['error']
            ]);
            
            // If it's still within expiry time, reschedule the job
            if (!$this->payment->expires_at || now()->lt($this->payment->expires_at)) {
                CheckBakongPaymentStatus::dispatch($this->payment)->delay(now()->addSeconds(30));
            }
        }
    }
}
