<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('payment_gateway')->nullable()->after('payment_status');
            $table->string('payment_reference')->nullable()->after('payment_gateway');
            $table->string('transaction_id')->nullable()->after('payment_reference');
            $table->text('qr_string')->nullable()->after('transaction_id');
            $table->json('gateway_response')->nullable()->after('qr_string');
            $table->json('callback_data')->nullable()->after('gateway_response');
            $table->timestamp('expires_at')->nullable()->after('callback_data');
            
            // Temporarily add a new column with the expanded enum
            $table->enum('temp_payment_status', ['paid', 'failed', 'pending'])->default('pending')->after('payment_status');
        });
        
        // Copy data from old column to new column
        \DB::statement('UPDATE payments SET temp_payment_status = payment_status');
        
        // Drop the old column and rename the new one
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('payment_status');
        });
        
        Schema::table('payments', function (Blueprint $table) {
            $table->renameColumn('temp_payment_status', 'payment_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Create a temporary column with the original enum
        Schema::table('payments', function (Blueprint $table) {
            $table->enum('temp_payment_status_old', ['paid', 'failed'])->default('failed');
        });
        
        // Copy data back (only keep valid values for the old enum)
        \DB::statement('UPDATE payments SET temp_payment_status_old = payment_status WHERE payment_status IN ("paid", "failed")');
        
        // Drop the current payment_status column
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('payment_status');
        });
        
        // Rename the old column back
        Schema::table('payments', function (Blueprint $table) {
            $table->renameColumn('temp_payment_status_old', 'payment_status');
        });
        
        // Remove the added columns
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn([
                'payment_gateway',
                'payment_reference', 
                'transaction_id',
                'qr_string',
                'gateway_response',
                'callback_data',
                'expires_at'
            ]);
        });
    }
};
