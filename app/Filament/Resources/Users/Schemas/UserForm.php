<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use App\Models\System;
use App\Models\User;
use App\Services\UserSystemRoles;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;

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
                TextInput::make('idagente')
                    ->label('ID de agente')
                    ->numeric()
                    ->nullable()
                    ->rules(['integer', 'between:-2147483648,2147483647'])
                    ->dehydrateStateUsing(fn ($state) => blank($state) ? null : $state),
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
                            ->options(fn () => System::pluck('name', 'id'))
                            ->placeholder('Sin sistema')
                            ->live()
                            ->afterStateUpdated(fn (Set $set) => $set('role_id', null))
                            ->searchable(),
                        Select::make('role_id')
                            ->label('Rol')
                            ->options(fn (Get $get, ?User $record) => app(UserSystemRoles::class)->options($record, $get('system_id')))
                            ->getOptionLabelUsing(function ($value) {
                                $roleClass = config('permission.models.role');

                                return $roleClass::find($value)?->name;
                            })
                            ->required()
                            ->searchable(),
                    ])
                    ->columns(2)
                    ->defaultItems(0)
                    ->addActionLabel('Agregar sistema y rol'),
            ]);
    }
}
