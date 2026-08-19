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
        Schema::dropIfExists('commission_payments');
        Schema::dropIfExists('commissions');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('expense_categories');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
