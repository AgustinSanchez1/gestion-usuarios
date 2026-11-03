<?php

namespace App\Filament\Resources\Roles\Tables;

use App\Models\System;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Spatie\Permission\Models\Role;

class RolesTable
{
    public static function configure(Table $table): Table
    {
        $systems = System::query()->pluck('name', 'id');

        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nombre del rol')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('system_name')
                    ->label('Sistema')
                    ->getStateUsing(fn (Role $record): string => $record->team_id === null
                        ? 'Global'
                        : ($systems->get($record->team_id) ?? 'Sistema no disponible')),
                TextColumn::make('permissions_count')
                    ->label('Permisos')
                    ->counts('permissions')
                    ->badge()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
