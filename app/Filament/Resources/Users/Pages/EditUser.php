<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Services\UserSystemRoles;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make(), ForceDeleteAction::make(), RestoreAction::make()];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        unset($data['password']);
        $data['system_roles'] = app(UserSystemRoles::class)->load($this->getRecord());

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        return app(UserSystemRoles::class)->update($record, $data);
    }
}
