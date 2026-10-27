<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('payment_method', 20)->nullable()->after('status');
            $table->string('payment_reference', 100)->nullable()->after('payment_method');
            $table->string('payment_proof_path')->nullable()->after('payment_reference');
            $table->timestamp('payment_submitted_at')->nullable()->after('payment_proof_path');
            $table->timestamp('payment_verified_at')->nullable()->after('payment_submitted_at');
            $table->foreignId('payment_verified_by')->nullable()->after('payment_verified_at')
                ->constrained('users')->nullOnDelete();
            $table->text('payment_rejection_reason')->nullable()->after('payment_verified_by');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['payment_verified_by']);
            $table->dropColumn([
                'payment_method',
                'payment_reference',
                'payment_proof_path',
                'payment_submitted_at',
                'payment_verified_at',
                'payment_verified_by',
                'payment_rejection_reason',
            ]);
        });
    }
};
