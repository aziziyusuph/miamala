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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('customer_name', 120);
            $table->string('phone', 30);
            $table->string('provider', 50);
            $table->string('transaction_id', 100)->nullable()->unique();
            $table->string('category', 80);
            $table->decimal('amount', 15, 2);
            $table->string('status', 20)->default('pending');
            $table->dateTime('payment_date');
            $table->string('order_reference', 100)->nullable();
            $table->decimal('expected_amount', 15, 2)->nullable();
            $table->boolean('reconciled')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('phone');
            $table->index('provider');
            $table->index('status');
            $table->index('category');
            $table->index('payment_date');
            $table->index('order_reference');
            $table->index('reconciled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
