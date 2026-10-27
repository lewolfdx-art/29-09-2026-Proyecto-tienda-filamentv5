<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

trait Auditable
{
    use LogsActivity;

    protected function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly($this->auditAttributes())
            ->logExcept($this->auditExcludedAttributes())
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName($this->auditLogName())
            ->setDescriptionForEvent(fn (string $eventName): string => $this->auditDescription($eventName));
    }

    /**
     * @return array<int, string>
     */
    protected function auditAttributes(): array
    {
        return $this->getFillable();
    }

    /**
     * @return array<int, string>
     */
    protected function auditExcludedAttributes(): array
    {
        return ['password', 'remember_token'];
    }

    protected function auditLogName(): string
    {
        return Str::snake(Str::pluralStudly(class_basename($this)));
    }

    protected function auditEntityName(): string
    {
        return match (class_basename($this)) {
            'Product' => 'producto',
            'Category' => 'categoría',
            'Customer' => 'cliente',
            'Order' => 'pedido',
            'OrderItem' => 'artículo de pedido',
            'User' => 'usuario',
            default => 'registro',
        };
    }

    protected function auditDescription(string $eventName): string
    {
        $subject = $this->auditSubjectLabel();

        return match ($eventName) {
            'created' => "Creación de {$subject}",
            'updated' => "Actualización de {$subject}",
            'deleted' => "Eliminación de {$subject}",
            'restored' => "Restauración de {$subject}",
            default => ucfirst($eventName)." de {$subject}",
        };
    }

    protected function auditSubjectLabel(): string
    {
        $entity = $this->auditEntityName();
        $name = $this->getAttribute('name');

        if (filled($name)) {
            return $entity.' «'.$name.'»';
        }

        $id = $this->getKey();

        return filled($id) ? $entity.' #'.$id : $entity;
    }
}
