<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! app()->environment(['local', 'testing']) && (DB::table('users')->exists() || DB::table('transactions')->exists())) {
            throw new RuntimeException('Ownership migration requires an explicit business mapping for existing users and transactions.');
        }

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('business_id')->nullable()->after('id')->constrained()->restrictOnDelete();
            $table->index(['business_id', 'email']);
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('business_id')->nullable()->after('id')->constrained()->restrictOnDelete();
            $table->index(['business_id', 'payment_date']);
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['business_id']);
            $table->dropIndex(['business_id', 'payment_date']);
            $table->dropColumn('business_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['business_id']);
            $table->dropIndex(['business_id', 'email']);
            $table->dropColumn('business_id');
        });
    }
};
