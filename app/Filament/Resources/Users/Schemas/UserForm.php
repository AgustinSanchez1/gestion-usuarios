<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use App\Models\System;
use Spatie\Permission\Models\Role;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name_user')
                    ->label('Usuario')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                TextInput::make('name')
                    ->label('Nombre completo')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->label('Correo electrónico')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                TextInput::make('password')
                    ->label('Contraseña')
                    ->password()
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->minLength(8)
                    ->maxLength(255)
                    ->dehydrated(fn ($state): bool => filled($state)),
                Repeater::make('system_roles')
                    ->label('Roles por sistema')
                    ->schema([
                        Select::make('system_id')
                            ->label('Sistema')
                            ->options(System::pluck('name', 'id'))
                            ->required()
                            ->searchable(),
                        Select::make('role_id')
                            ->label('Rol')
                            ->options(Role::pluck('name', 'id'))
                            ->required()
                            ->searchable(),
                    ])
                    ->columns(2)
                    ->defaultItems(0)
                    ->addActionLabel('Agregar sistema y rol'),
            ]);
    }
}
