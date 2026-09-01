<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('reconciliation_status', 20)->default('unreconciled')->after('reconciled');
            $table->index('reconciliation_status');
        });

        DB::table('transactions')->update([
            'reconciliation_status' => DB::raw("CASE
                WHEN expected_amount IS NULL OR order_reference IS NULL OR TRIM(order_reference) = '' THEN 'unreconciled'
                WHEN ABS(amount - expected_amount) <= 0.01 THEN 'exact_match'
                WHEN amount < expected_amount THEN 'underpaid'
                ELSE 'overpaid'
            END"),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex(['reconciliation_status']);
            $table->dropColumn('reconciliation_status');
        });
    }
};
