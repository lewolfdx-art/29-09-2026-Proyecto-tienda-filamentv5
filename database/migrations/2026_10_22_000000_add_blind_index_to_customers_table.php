<?php

use App\Support\BlindIndex;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('phone_hash', 64)->nullable()->after('phone')->index();
            $table->text('address_tokens')->nullable()->after('address');
        });

        DB::table('customers')->orderBy('id')->each(function ($row) {
            DB::table('customers')->where('id', $row->id)->update([
                'phone_hash' => self::phoneHash($row->phone),
                'address_tokens' => self::addressTokens($row->address),
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex(['phone_hash']);
            $table->dropColumn(['phone_hash', 'address_tokens']);
        });
    }

    private static function phoneHash(?string $phone): ?string
    {
        $value = self::decrypt($phone);

        return $value === null ? null : BlindIndex::hashPhone($value);
    }

    private static function addressTokens(?string $address): ?string
    {
        $value = self::decrypt($address);

        return $value === null ? null : BlindIndex::tokenList($value);
    }

    /**
     * Los datos están cifrados, pero si algún valor se guardó en claro
     * (por ejemplo antes de la migración que los cifró) se usa tal cual.
     */
    private static function decrypt(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (Throwable) {
            return $value;
        }
    }
};
