<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PortalPanelResource\Pages;
use App\Models\PortalPanel;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;

class PortalPanelResource extends Resource
{
    protected static ?string $model = PortalPanel::class;

    protected static ?string $navigationIcon  = 'heroicon-o-squares-2x2';
    protected static ?string $navigationLabel = 'Portal Panel';
    protected static ?string $navigationGroup = 'Pengaturan';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(2)->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Nama Panel')
                        ->required()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('code')
                        ->label('Kode')
                        ->helperText('Kode unik, contoh: rab, form, qc')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(100),
                ]),

                Forms\Components\TextInput::make('url')
                    ->label('URL Path')
                    ->helperText('Contoh: /admin, /rab, /form, /qc')
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('badge')
                    ->label('Badge / Label Kecil')
                    ->placeholder('Keuangan / Marcomm, QC / Teknisi, dst.')
                    ->maxLength(255),

                Forms\Components\Textarea::make('description')
                    ->label('Deskripsi')
                    ->rows(3)
                    ->maxLength(1000),

                Forms\Components\Grid::make(2)->schema([
                    Forms\Components\Toggle::make('is_active')
                        ->label('Aktif')
                        ->default(true),

                    Forms\Components\TextInput::make('sort_order')
                        ->label('Urutan')
                        ->numeric()
                        ->placeholder('Semakin kecil semakin atas'),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sort_order')
                    ->label('#')

                    ->width('40px'),

                TextColumn::make('name')
                    ->label('Nama Panel')
                    ->searchable(),

                TextColumn::make('code')
                    ->label('Kode')
                    ->badge(),

                TextColumn::make('url')
                    ->label('URL')
                    ->searchable(),

                TextColumn::make('badge')
                    ->label('Badge')
                    ->wrap(),

                TextColumn::make('is_active')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn(bool $state) => $state ? 'Aktif' : 'Nonaktif')
                    ->color(fn(bool $state) => $state ? 'success' : 'gray'),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status')
                    ->trueLabel('Aktif')
                    ->falseLabel('Nonaktif')
                    ->placeholder('Semua'),   // ⬅️ perbaikan: pakai placeholder(), bukan nullableLabel()

                TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\Action::make('history')
                    ->label('History')
                    ->icon('heroicon-o-clock')
                    ->color('info')
                    ->modalHeading('Log Aktivitas')
                    ->modalContent(fn($record) => view('filament.components.system-history-modal', ['record' => $record]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup'),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
                Tables\Actions\ForceDeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
                Tables\Actions\RestoreBulkAction::make(),
                Tables\Actions\ForceDeleteBulkAction::make(),
            ])
            ->defaultSort('sort_order');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListPortalPanels::route('/'),
            'create' => Pages\CreatePortalPanel::route('/create'),
            'edit'   => Pages\EditPortalPanel::route('/{record}/edit'),
        ];
    }
    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasRole('superadmin');
    }
    public static function canViewAny(): bool
    {
        return auth()->user()?->hasRole('superadmin');
    }
}
