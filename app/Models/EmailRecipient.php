<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Database\Factories\EmailRecipientFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $user_id
 * @property string $email
 * @property array<int, string> $tipos
 * @property int $frecuencia_dias
 * @property bool $activo
 * @property Carbon|null $last_backup_sent_at
 * @property-read User|null $user
 *
 * @method static Builder<static> active()
 * @method static Builder<static> ofType(string $type)
 */
class EmailRecipient extends Model
{
    /** @use HasFactory<EmailRecipientFactory> */
    use Auditable, HasFactory;

    public const TYPE_NEW_ORDER = 'pedido_nuevo';

    public const TYPE_PAYMENT_PROOF = 'comprobante_pago';

    public const TYPE_BACKUP_FAILURE = 'alerta_backup';

    public const TYPE_LOW_STOCK = 'stock_bajo';

    public const TYPE_BACKUP_ATTACHMENT = 'backup_adjunto';

    public const TYPE_OPTIONS = [
        self::TYPE_NEW_ORDER => 'Pedido nuevo',
        self::TYPE_PAYMENT_PROOF => 'Comprobante de pago',
        self::TYPE_BACKUP_FAILURE => 'Falla de backup',
        self::TYPE_LOW_STOCK => 'Stock bajo',
        self::TYPE_BACKUP_ATTACHMENT => 'Backup adjunto',
    ];

    protected $fillable = [
        'user_id', 'email', 'tipos', 'frecuencia_dias', 'activo', 'last_backup_sent_at',
    ];

    protected static function booted(): void
    {
        static::saving(function (EmailRecipient $recipient): void {
            if (! $recipient->user_id) {
                return;
            }

            $email = User::query()->whereKey($recipient->user_id)->value('email');

            if (filled($email)) {
                $recipient->email = (string) $email;
            }
        });
    }

    protected function casts(): array
    {
        return [
            'tipos' => 'array',
            'frecuencia_dias' => 'integer',
            'activo' => 'boolean',
            'last_backup_sent_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('activo', true);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->whereJsonContains('tipos', $type);
    }

    public function effectiveEmail(): string
    {
        return (string) ($this->user?->email ?: $this->email);
    }

    public function isDueForBackup(): bool
    {
        if (! $this->last_backup_sent_at) {
            return true;
        }

        return $this->last_backup_sent_at->lte(now()->subDays(max(1, $this->frecuencia_dias)));
    }
}
