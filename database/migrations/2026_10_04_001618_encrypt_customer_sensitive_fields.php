<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // El texto cifrado es más largo que el original: se necesita TEXT.
        Schema::table('customers', function (Blueprint $table) {
            $table->text('phone')->nullable()->change();
            $table->text('address')->nullable()->change();
        });

        // Cifra los datos que ya existen.
        DB::table('customers')->orderBy('id')->each(function ($row) {
            DB::table('customers')->where('id', $row->id)->update([
                'phone' => $row->phone !== null ? Crypt::encryptString($row->phone) : null,
                'address' => $row->address !== null ? Crypt::encryptString($row->address) : null,
            ]);
        });
    }

    public function down(): void
    {
        DB::table('customers')->orderBy('id')->each(function ($row) {
            DB::table('customers')->where('id', $row->id)->update([
                'phone' => $row->phone !== null ? Crypt::decryptString($row->phone) : null,
                'address' => $row->address !== null ? Crypt::decryptString($row->address) : null,
            ]);
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->string('phone')->nullable()->change();
            $table->string('address')->nullable()->change();
        });
    }
};