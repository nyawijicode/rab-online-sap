<?php

namespace App\Filament\Resources\RequestTeknisiResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class HistoriesRelationManager extends RelationManager
{
    protected static string $relationship = 'histories';

    protected static ?string $title = 'Log Aktivitas';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('description')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal')
                    ->dateTime('d M Y H:i:s'),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('User'),
                Tables\Columns\TextColumn::make('description')
                    ->label('Aktivitas')
                    ->wrap(),
                Tables\Columns\TextColumn::make('field')
                    ->label('Field')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('old_value')
                    ->label('Lama')
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('new_value')
                    ->label('Baru')
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                // No create action, it's auto logged
            ])
            ->actions([
                // Read only
            ])
            ->bulkActions([
                // Read only
            ])
            ->defaultSort('created_at', 'desc');
    }
}
