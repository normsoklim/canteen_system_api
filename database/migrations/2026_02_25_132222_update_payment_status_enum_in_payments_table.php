<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update the payment_status enum to include 'pending'
        DB::statement("ALTER TABLE payments MODIFY payment_status ENUM('paid', 'failed', 'pending') DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to the original enum values
        DB::statement("ALTER TABLE payments MODIFY payment_status ENUM('paid', 'failed') DEFAULT 'failed'");
    }
};
