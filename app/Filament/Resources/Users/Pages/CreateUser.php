<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Preservar los datos del repeater para usarlos después de crear
        $data['_temp_system_roles'] = $data['system_roles'] ?? [];
        unset($data['system_roles']);

        // Hash password si se proporcionó
        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        $record = $this->record;
        $data = $this->form->getRawState();
        $systemRoles = $data['_temp_system_roles'] ?? [];

        $this->syncSystemRoles($record, $systemRoles);
    }

    protected function syncSystemRoles($user, array $systemRoles): void
    {
        // Eliminar todos los roles actuales (sin importar equipo)
        $user->syncRoles([]);

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
        app(\Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId(null);
    }
}
