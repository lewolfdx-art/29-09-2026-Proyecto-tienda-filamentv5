<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

trait HasAutoSlug
{
    protected static function bootHasAutoSlug(): void
    {
        static::creating(function ($model) {
            $model->slug = static::generateUniqueSlug($model->name);
        });
    }

    protected static function generateUniqueSlug(?string $name): string
    {
        $base = Str::slug($name ?? '') ?: 'item';
        $slug = $base;
        $counter = 2;

        while (static::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $counter++;
        }

        return $slug;
    }
}