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
        // Check if columns exist before adding them
        $columnsToAdd = [];
        
        if (!Schema::hasColumn('payments', 'payment_gateway')) {
            $columnsToAdd[] = function (Blueprint $table) {
                $table->string('payment_gateway')->nullable()->after('payment_status');
            };
        }
        
        if (!Schema::hasColumn('payments', 'payment_reference')) {
            $columnsToAdd[] = function (Blueprint $table) {
                $table->string('payment_reference')->nullable()->after('payment_gateway');
            };
        }
        
        if (!Schema::hasColumn('payments', 'transaction_id')) {
            $columnsToAdd[] = function (Blueprint $table) {
                $table->string('transaction_id')->nullable()->after('payment_reference');
            };
        }
        
        if (!Schema::hasColumn('payments', 'qr_string')) {
            $columnsToAdd[] = function (Blueprint $table) {
                $table->text('qr_string')->nullable()->after('transaction_id');
            };
        }
        
        if (!Schema::hasColumn('payments', 'gateway_response')) {
            $columnsToAdd[] = function (Blueprint $table) {
                $table->json('gateway_response')->nullable()->after('qr_string');
            };
        }
        
        if (!Schema::hasColumn('payments', 'callback_data')) {
            $columnsToAdd[] = function (Blueprint $table) {
                $table->json('callback_data')->nullable()->after('gateway_response');
            };
        }
        
        if (!Schema::hasColumn('payments', 'expires_at')) {
            $columnsToAdd[] = function (Blueprint $table) {
                $table->timestamp('expires_at')->nullable()->after('callback_data');
            };
        }
        
        // Add the columns that don't exist
        foreach ($columnsToAdd as $addColumn) {
            Schema::table('payments', $addColumn);
        }
        
        // Update the payment_status enum if it doesn't have 'pending'
        if (Schema::hasColumn('payments', 'payment_status')) {
            // For this version, we'll just ensure the column exists with the right type
            // Changing enum values can be complex, so we'll leave it as is for now
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
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
