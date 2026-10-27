<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Support\BlindIndex;
use App\Support\Search;
use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Customer extends Authenticatable
{
    /** @use HasFactory<CustomerFactory> */
    use Auditable, HasFactory;

    protected $fillable = ['name', 'email', 'phone', 'address', 'city', 'password'];

    protected $hidden = ['password', 'remember_token', 'phone_hash', 'address_tokens'];

    protected static function booted(): void
    {
        // El teléfono y la dirección se guardan cifrados, así que no se pueden
        // buscar directamente. Se mantiene un índice ciego de cada uno para
        // que el panel pueda encontrarlos.
        static::saving(function (Customer $customer) {
            if ($customer->isDirty('phone')) {
                $customer->phone_hash = BlindIndex::hashPhone((string) $customer->phone);
            }

            if ($customer->isDirty('address')) {
                $customer->address_tokens = BlindIndex::tokenList((string) $customer->address);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'phone' => 'encrypted',
            'address' => 'encrypted',
            'password' => 'hashed',
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function auditExcludedAttributes(): array
    {
        return ['password', 'remember_token', 'phone', 'address', 'phone_hash', 'address_tokens'];
    }

    /**
     * Busca clientes por nombre, email o ciudad (texto plano) y por teléfono
     * o dirección (índice ciego).
     */
    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        $search = trim((string) $search);

        if ($search === '') {
            return $query;
        }

        $name = $query->qualifyColumn('name');
        $email = $query->qualifyColumn('email');
        $city = $query->qualifyColumn('city');
        $phoneHash = $query->qualifyColumn('phone_hash');
        $addressTokens = $query->qualifyColumn('address_tokens');

        $like = Search::pattern($search);
        $phone = BlindIndex::hashPhone($search);

        return $query->where(function (Builder $q) use ($name, $email, $city, $phoneHash, $addressTokens, $like, $phone, $search) {
            $q->where(function (Builder $text) use ($name, $email, $city, $like) {
                $text->whereRaw("{$name} LIKE ? ESCAPE ?", [$like, Search::ESCAPE])
                    ->orWhereRaw("{$email} LIKE ? ESCAPE ?", [$like, Search::ESCAPE])
                    ->orWhereRaw("{$city} LIKE ? ESCAPE ?", [$like, Search::ESCAPE]);
            });

            if ($phone !== null) {
                $q->orWhere($phoneHash, $phone);
            }

            $tokens = BlindIndex::tokens($search);

            if ($tokens !== []) {
                $q->orWhere(function (Builder $address) use ($addressTokens, $tokens) {
                    foreach ($tokens as $token) {
                        $address->whereRaw("{$addressTokens} LIKE ?", ['%'.BlindIndex::hash('address:'.$token).'%']);
                    }
                });
            }
        });
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
