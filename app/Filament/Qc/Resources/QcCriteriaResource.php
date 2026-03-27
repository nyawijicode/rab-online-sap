<?php

namespace App\Filament\Qc\Resources;

use App\Filament\Qc\Resources\QcCriteriaResource\Pages;
use App\Models\QcCriteria;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class QcCriteriaResource extends Resource
{
    protected static ?string $model = QcCriteria::class;

    protected static ?string $navigationIcon = 'heroicon-o-list-bullet';
    protected static ?string $navigationGroup = 'Master Data';
    protected static ?string $navigationLabel = 'Kriteria QC';
    protected static ?string $label = 'Kriteria QC';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()

                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()

                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQcCriterias::route('/'),
        ];
    }
    public static function canViewAny(): bool
    {
        $user = Auth::user();

        return $user
            && (
                $user->hasRole('superadmin')
                || $user->hasRole('koordinator teknisi')
            );
    }
    public static function canView($record): bool
    {
        return static::canViewAny();
    }
}
