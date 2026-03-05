<?php

namespace App\Console\Commands;

use App\Models\Payment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessExpiredBakongPayments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bakong:process-expired-payments';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process and mark expired Bakong payments as failed';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Processing expired Bakong payments...');

        // Find all pending Bakong payments that have expired
        $expiredPayments = Payment::where('payment_gateway', 'bakong')
            ->where('payment_status', 'pending')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->get();

        $count = 0;
        foreach ($expiredPayments as $payment) {
            // Update payment status to failed
            $payment->update([
                'payment_status' => 'failed',
                'gateway_response' => array_merge(
                    (array)$payment->gateway_response,
                    [
                        'status' => 'expired',
                        'message' => 'Payment expired at ' . $payment->expires_at,
                        'processed_by' => 'cron_job',
                        'processed_at' => now()
                    ]
                )
            ]);

            // Update the associated order status
            if ($payment->order) {
                $payment->order->update([
                    'payment_status' => 'unpaid'
                ]);
            }

            $count++;
            Log::info("Marked expired Bakong payment as failed: {$payment->id}");
        }

        $this->info("Processed {$count} expired Bakong payments.");
    }
}
