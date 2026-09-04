<?php

namespace App\Filament\Resources\Systems\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rule;

class SystemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('slug')
                    ->label('Slug')
                    ->required()
                    ->maxLength(100)
                    ->unique(ignoreRecord: true)
                    ->rule('regex:/^[a-z0-9_-]+$/')
                    ->validationMessages([
                        'regex' => 'El slug solo puede contener letras minúsculas, números, guiones y guiones bajos.',
                    ]),
                TextInput::make('nombre')
                    ->label('Nombre')
                    ->required()
                    ->maxLength(255),
            ]);
    }
}