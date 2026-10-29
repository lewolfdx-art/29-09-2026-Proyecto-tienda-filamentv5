<?php

namespace Database\Factories;

use App\Models\EmailRecipient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmailRecipient>
 */
class EmailRecipientFactory extends Factory
{
    protected $model = EmailRecipient::class;

    public function definition(): array
    {
        return [
            'user_id' => null,
            'email' => fake()->unique()->safeEmail(),
            'tipos' => [EmailRecipient::TYPE_NEW_ORDER],
            'frecuencia_dias' => 14,
            'activo' => true,
            'last_backup_sent_at' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['activo' => false]);
    }
}
