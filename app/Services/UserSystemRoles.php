<?php

namespace App\Services;

use App\Models\System;
use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Guard;

class UserSystemRoles
{
    private function assignments(User $user): Builder
    {
        return $user->getConnection()->table(config('permission.table_names.model_has_roles'))
            ->where(config('permission.column_names.model_morph_key', 'model_id'), $user->getKey())
            ->where('model_type', $user->getMorphClass());
    }

    public function load(User $user): array
    {
        $teamKey = config('permission.column_names.team_foreign_key', 'team_id');
        $roleKey = config('permission.column_names.role_pivot_key') ?: 'role_id';

        return $this->assignments($user)->orderBy($teamKey)->orderBy($roleKey)->get()
            ->map(fn ($row) => [
                'system_id' => $row->$teamKey === null ? '' : (string) $row->$teamKey,
                'role_id' => (string) $row->$roleKey,
            ])->all();
    }

    public function options(?User $user, mixed $systemId): array
    {
        $roleClass = config('permission.models.role');
        $teamKey = config('permission.column_names.team_foreign_key', 'team_id');

        return $roleClass::query()
            ->whereIn('guard_name', Guard::getNames($user ?? new User()))
            ->where(function ($query) use ($teamKey, $systemId) {
                $query->whereNull($teamKey);
                if (filled($systemId)) {
                    $query->orWhere($teamKey, $systemId);
                }
            })
            ->orderBy('name')->get()
            ->mapWithKeys(fn ($role) => [
                $role->getKey() => $role->name . ($role->$teamKey === null ? ' (global)' : ''),
            ])->all();
    }

    public function update(User $user, array $data): User
    {
        // This editor spans all systems without changing the registrar's team.
        try {
            return $user->getConnection()->transaction(function () use ($user, $data) {
                $user->newQuery()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();
                $existing = $this->load($user);
                $rows = $data['system_roles'] ?? $existing;
                unset($data['system_roles']);
                $desired = [];
                foreach ($rows as $index => $row) {
                    $system = filled($row['system_id'] ?? null) ? (string) $row['system_id'] : '';
                    $role = (string) ($row['role_id'] ?? '');
                    $key = $system . ':' . $role;
                    $unchanged = in_array(['system_id' => $system, 'role_id' => $role], $existing, true);
                    if (isset($desired[$key]) || (! $unchanged && (
                        ($system !== '' && ! System::whereKey($system)->exists())
                        || ! array_key_exists($role, $this->options($user, $system))
                    ))) {
                        throw ValidationException::withMessages([
                            "data.system_roles.{$index}.role_id" => 'La asignación es duplicada o el rol no es válido para este sistema.',
                        ]);
                    }
                    $desired[$key] = ['system_id' => $system, 'role_id' => $role];
                }

                $teamKey = config('permission.column_names.team_foreign_key', 'team_id');
                $roleKey = config('permission.column_names.role_pivot_key') ?: 'role_id';
                foreach ($existing as $row) {
                    if (! isset($desired[$row['system_id'] . ':' . $row['role_id']])) {
                        $query = $this->assignments($user)->where($roleKey, $row['role_id']);
                        $row['system_id'] === ''
                            ? $query->whereNull($teamKey)
                            : $query->where($teamKey, $row['system_id']);
                        $query->delete();
                    }
                }
                foreach ($desired as $row) {
                    if (! in_array($row, $existing, true)) {
                        $this->assignments($user)->insert([
                            config('permission.column_names.model_morph_key', 'model_id') => $user->getKey(),
                            'model_type' => $user->getMorphClass(),
                            $teamKey => $row['system_id'] === '' ? null : $row['system_id'],
                            $roleKey => $row['role_id'],
                        ]);
                    }
                }
                $user->update($data);

                return $user;
            });
        } finally {
            $user->unsetRelation('roles')->unsetRelation('permissions');
        }
    }
}
