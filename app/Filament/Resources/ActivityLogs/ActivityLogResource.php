<?php

namespace App\Filament\Resources\ActivityLogs;

use App\Filament\Resources\ActivityLogs\Pages\ListActivityLogs;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Activitylog\Models\Activity;

class ActivityLogResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static ?string $recordTitleAttribute = 'description';

    protected static ?string $navigationLabel = 'Registro de actividad';

    protected static ?string $modelLabel = 'evento';

    protected static ?string $pluralModelLabel = 'eventos';

    public static function getNavigationLabel(): string
    {
        return 'Registro de actividad';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('description')
                    ->label('Descripción')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('log_name')
                    ->label('Módulo')
                    ->badge()
                    ->placeholder('-'),
                TextColumn::make('event')
                    ->label('Evento')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => self::eventLabel($state))
                    ->color(fn (?string $state): string => match ($state) {
                        'created' => 'success',
                        'updated' => 'warning',
                        'deleted' => 'danger',
                        'restored' => 'info',
                        default => 'gray',
                    }),
                TextColumn::make('causer.name')
                    ->label('Usuario')
                    ->placeholder('Sistema'),
            ])
            ->filters([
                SelectFilter::make('log_name')
                    ->label('Módulo')
                    ->options(fn (): array => Activity::query()
                        ->whereNotNull('log_name')
                        ->distinct()
                        ->orderBy('log_name')
                        ->pluck('log_name', 'log_name')
                        ->all()),
                SelectFilter::make('event')
                    ->label('Evento')
                    ->options([
                        'created' => 'Creación',
                        'updated' => 'Actualización',
                        'deleted' => 'Eliminación',
                        'restored' => 'Restauración',
                    ]),
                Filter::make('created_at')
                    ->label('Rango de fechas')
                    ->schema([
                        DatePicker::make('desde')
                            ->label('Desde'),
                        DatePicker::make('hasta')
                            ->label('Hasta'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['desde'] ?? null, fn (Builder $q, string $date) => $q->whereDate('created_at', '>=', $date))
                        ->when($data['hasta'] ?? null, fn (Builder $q, string $date) => $q->whereDate('created_at', '<=', $date))),
            ])
            ->recordActions([
                ViewAction::make()
                    ->schema(fn (Activity $record): array => [
                        TextEntry::make('description')
                            ->label('Descripción'),
                        TextEntry::make('log_name')
                            ->label('Módulo')
                            ->placeholder('-'),
                        TextEntry::make('event')
                            ->label('Evento')
                            ->formatStateUsing(fn (?string $state): string => self::eventLabel($state)),
                        TextEntry::make('causer.name')
                            ->label('Usuario')
                            ->placeholder('Sistema'),
                        TextEntry::make('subject_type')
                            ->label('Entidad')
                            ->formatStateUsing(fn (?string $state): string => self::entityLabel($state)),
                        TextEntry::make('subject_id')
                            ->label('ID de la entidad')
                            ->placeholder('-'),
                        TextEntry::make('created_at')
                            ->label('Fecha')
                            ->dateTime(),
                        TextEntry::make('cambios')
                            ->label('Cambios')
                            ->state(self::formatChanges($record))
                            ->columnSpanFull()
                            ->extraAttributes(['class' => 'whitespace-pre-wrap font-mono text-sm']),
                    ]),
            ]);
    }

    private static function eventLabel(?string $event): string
    {
        return match ($event) {
            'created' => 'Creación',
            'updated' => 'Actualización',
            'deleted' => 'Eliminación',
            'restored' => 'Restauración',
            default => $event ?? '-',
        };
    }

    private static function entityLabel(?string $type): string
    {
        if ($type === null) {
            return '-';
        }

        return match (class_basename($type)) {
            'Product' => 'Producto',
            'Category' => 'Categoría',
            'Customer' => 'Cliente',
            'Order' => 'Pedido',
            'OrderItem' => 'Artículo de pedido',
            'User' => 'Usuario',
            default => class_basename($type),
        };
    }

    private static function formatChanges(Activity $record): string
    {
        $attributes = (array) ($record->properties['attributes'] ?? []);
        $old = (array) ($record->properties['old'] ?? []);

        if ($attributes === [] && $old === []) {
            return 'Sin cambios registrados.';
        }

        $lines = [];

        foreach ($attributes as $field => $value) {
            $lines[] = array_key_exists($field, $old)
                ? $field.': '.self::formatValue($old[$field]).' → '.self::formatValue($value)
                : $field.': '.self::formatValue($value);
        }

        foreach (array_diff_key($old, $attributes) as $field => $value) {
            $lines[] = $field.': '.self::formatValue($value).' → —';
        }

        return implode("\n", $lines);
    }

    private static function formatValue(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        if (is_bool($value)) {
            return $value ? 'sí' : 'no';
        }

        if (is_array($value)) {
            return (string) json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return (string) $value;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(mixed $record): bool
    {
        return false;
    }

    public static function canDelete(mixed $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListActivityLogs::route('/'),
        ];
    }
}
