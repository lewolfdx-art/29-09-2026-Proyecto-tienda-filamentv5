<?php

namespace App\Filament\Resources\EmailRecipients\Pages;

use App\Filament\Resources\EmailRecipients\EmailRecipientResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageEmailRecipients extends ManageRecords
{
    protected static string $resource = EmailRecipientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
