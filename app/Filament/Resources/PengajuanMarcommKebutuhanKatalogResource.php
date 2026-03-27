<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PengajuanMarcommKebutuhanKatalogResource\Pages;
use App\Filament\Resources\PengajuanMarcommKebutuhanKatalogResource\RelationManagers;
use App\Models\PengajuanMarcommKebutuhanKatalog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PengajuanMarcommKebutuhanKatalogResource extends Resource
{
    protected static ?string $model = PengajuanMarcommKebutuhanKatalog::class;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';
    protected static ?string $navigationGroup = 'Detail RAB Marcomm';
    protected static ?string $label = 'Katalog';
    protected static ?string $pluralLabel = 'Katalog';
    protected static ?string $slug = 'detail-katalog';
    protected static ?int $navigationSort = 111;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('pengajuan_id')
                ->relationship('pengajuan', 'no_rab')
                ->required()
                ->searchable(),
            Forms\Components\TextInput::make('cabang')
                ->required()
                ->maxLength(255),
            Forms\Components\TextInput::make('jumlah')
                ->numeric()
                ->required()
                ->minValue(1),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('pengajuan.no_rab')->label('No RAB')->searchable(),
                Tables\Columns\TextColumn::make('cabang'),
                Tables\Columns\TextColumn::make('jumlah'),
            ])
            ->filters([
                //
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\Action::make('history')
                    ->label('History')
                    ->icon('heroicon-o-clock')
                    ->color('info')
                    ->modalHeading('Log Aktivitas')
                    ->modalContent(fn($record) => view('filament.components.system-history-modal', ['record' => $record]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPengajuanMarcommKebutuhanKatalogs::route('/'),
            'create' => Pages\CreatePengajuanMarcommKebutuhanKatalog::route('/create'),
            'edit' => Pages\EditPengajuanMarcommKebutuhanKatalog::route('/{record}/edit'),
        ];
    }
    public static function canCreate(): bool
    {
        return false; // Tidak bisa membuat data baru
    }
    public static function canEdit($record): bool
    {
        return false; // Matikan tombol Edit
    }

    public static function canDelete($record): bool
    {
        return false; // Matikan tombol Delete
    }
}
