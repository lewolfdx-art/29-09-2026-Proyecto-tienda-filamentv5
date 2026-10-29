<?php

namespace App\Filament\Resources\EmailRecipients;

use App\Filament\Resources\EmailRecipients\Pages\ManageEmailRecipients;
use App\Models\EmailRecipient;
use App\Models\User;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class EmailRecipientResource extends Resource
{
    protected static ?string $model = EmailRecipient::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static \UnitEnum|string|null $navigationGroup = 'Configuración';

    protected static ?int $navigationSort = 10;

    protected static ?string $navigationLabel = 'Destinatarios de correo';

    protected static ?string $modelLabel = 'destinatario de correo';

    protected static ?string $pluralModelLabel = 'destinatarios de correo';

    protected static ?string $recordTitleAttribute = 'email';

    public static function getNavigationLabel(): string
    {
        return 'Destinatarios de correo';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->label('Usuario (opcional)')
                    ->relationship('user', 'email')
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(function (?string $state, Set $set): void {
                        if (filled($state)) {
                            $set('email', User::query()->whereKey($state)->value('email'));
                        }
                    })
                    ->helperText('Si eliges un usuario, el correo se completa automáticamente.'),
                TextInput::make('email')
                    ->label('Correo electrónico')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->helperText('Puede ser un correo externo que no pertenezca a ningún usuario.'),
                CheckboxList::make('tipos')
                    ->label('Tipos de aviso')
                    ->options(EmailRecipient::TYPE_OPTIONS)
                    ->columns(2)
                    ->required()
                    ->live(),
                TextInput::make('frecuencia_dias')
                    ->label('Frecuencia de backup (días)')
                    ->numeric()
                    ->integer()
                    ->minValue(1)
                    ->maxValue(365)
                    ->default(14)
                    ->visible(fn (Get $get): bool => in_array(
                        EmailRecipient::TYPE_BACKUP_ATTACHMENT,
                        (array) $get('tipos'),
                        true,
                    ))
                    ->helperText('Solo se usa cuando el destinatario recibe el tipo "Backup adjunto".'),
                Toggle::make('activo')
                    ->label('Activo')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('email')
            ->columns([
                TextColumn::make('user.email')
                    ->label('Usuario')
                    ->placeholder('Sin usuario')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Correo')
                    ->searchable(),
                TextColumn::make('tipos')
                    ->label('Tipos')
                    ->formatStateUsing(fn (mixed $state): string => collect((array) $state)
                        ->map(fn (string $type): string => EmailRecipient::TYPE_OPTIONS[$type] ?? $type)
                        ->implode(', '))
                    ->wrap(),
                TextColumn::make('frecuencia_dias')
                    ->label('Frecuencia (días)')
                    ->sortable(),
                IconColumn::make('activo')
                    ->label('Activo')
                    ->boolean(),
                TextColumn::make('last_backup_sent_at')
                    ->label('Último backup enviado')
                    ->dateTime()
                    ->placeholder('Nunca')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Actualizado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('activo')
                    ->label('Estado')
                    ->placeholder('Todos')
                    ->trueLabel('Activos')
                    ->falseLabel('Inactivos'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageEmailRecipients::route('/'),
        ];
    }
}
