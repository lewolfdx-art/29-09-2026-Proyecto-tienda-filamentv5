<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('email');
            $table->json('tipos');
            $table->unsignedSmallInteger('frecuencia_dias')->default(14);
            $table->boolean('activo')->default(true);
            $table->timestamp('last_backup_sent_at')->nullable();
            $table->timestamps();

            $table->index(['activo', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_recipients');
    }
};
