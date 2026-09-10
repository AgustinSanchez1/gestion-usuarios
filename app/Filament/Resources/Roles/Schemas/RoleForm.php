<?php

namespace App\Filament\Resources\Roles\Schemas;

use App\Models\System;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Spatie\Permission\Models\Permission;

class RoleForm
{
    public static function configure(Schema $schema): Schema
    {

        $systems = System::all()->pluck('name', 'id');

        $permissions = Permission::all()->pluck('name', 'id');

        return $schema
            ->components([
                Select::make('team_id')
                    ->label('Sistema')
                    ->options($systems)
                    ->searchable()
                    ->preload()
                    ->placeholder('Sin sistema (global)'),
                TextInput::make('name')
                    ->label('Nombre del rol')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                Select::make('permissions')
                    ->label('Permisos')
                    ->multiple()
                    ->relationship('permissions', 'name')
                    ->options($permissions)
                    ->preload(),
            ]);
    }
}
