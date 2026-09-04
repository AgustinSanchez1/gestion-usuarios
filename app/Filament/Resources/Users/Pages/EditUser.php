<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    protected function mutateFormDataBeforeUpdate(array $data): array
    {
        // Eliminar system_roles para que no se intente guardar en el modelo User
        unset($data['system_roles']);

        // Hash password si se proporcionó
        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $record = $this->record;
        $systemRoles = $this->form->getRawState()['system_roles'] ?? [];

        $this->syncSystemRoles($record, $systemRoles);
    }

    protected function syncSystemRoles($user, array $systemRoles): void
    {
        // Eliminar todos los roles actuales (sin importar equipo)
        $user->syncRoles([]);
        $team_id = app(\Spatie\Permission\PermissionRegistrar::class)->getPermissionsTeamId();

        foreach ($systemRoles as $item) {
            $roleId = $item['role_id'] ?? null;
            $systemId = $item['system_id'] ?? null;

            if (! $roleId || ! $systemId) {
                continue;
            }

            $role = Role::find($roleId);
            if (! $role) {
                continue;
            }

            // Setear el equipo activo antes de asignar
            app(\Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId($systemId);

            $user->assignRole($role);
        }

        // Restaurar equipo activo
        app(\Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId($team_id);
    }
}
