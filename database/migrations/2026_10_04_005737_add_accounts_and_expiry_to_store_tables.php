<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('password')->nullable()->after('email');
            $table->rememberToken()->after('password');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('expires_at')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['password', 'remember_token']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('expires_at');
        });
    }
};