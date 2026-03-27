<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CompanyResource\Pages;
use App\Models\Company;
use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;

class CompanyResource extends Resource
{
    protected static ?string $model = Company::class;
    protected static ?string $navigationGroup = 'Pengaturan';
    protected static ?string $navigationIcon = 'heroicon-o-building-office';
    protected static ?string $label = 'Perusahaan';
    protected static ?string $navigationLabel = 'Perusahaan';
    protected static ?string $slug = 'companies';

    public static function form(Form $form): Form
    {
        return $form->schema([

            Forms\Components\Section::make('Data Perusahaan')
                ->description('Lengkapi informasi perusahaan dengan benar.')
                ->schema([
                    Forms\Components\TextInput::make('kode')
                        ->label('Kode')
                        ->maxLength(20)
                        ->required(),

                    Forms\Components\TextInput::make('nama_perusahaan')
                        ->label('Nama Perusahaan')
                        ->required()
                        ->maxLength(255),

                    Forms\Components\Textarea::make('deskripsi')
                        ->label('Deskripsi')
                        ->columnSpanFull(),

                    Forms\Components\Textarea::make('alamat')
                        ->label('Alamat')
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('telepon')
                        ->label('Telepon'),

                    Forms\Components\TextInput::make('email')
                        ->label('Email')
                        ->email(),
                ])
                ->columns(2), // Atur jumlah kolom dalam section
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([

            Tables\Columns\TextColumn::make('kode')->searchable(),
            Tables\Columns\TextColumn::make('nama_perusahaan')->searchable(),
            Tables\Columns\TextColumn::make('alamat'),
            Tables\Columns\TextColumn::make('telepon'),
            Tables\Columns\TextColumn::make('email'),
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
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCompanies::route('/'),
            'create' => Pages\CreateCompany::route('/create'),
            'edit'   => Pages\EditCompany::route('/{record}/edit'),
        ];
    }
    // ⛔ MUNCUL NANG MENU CUMA KALO SUPERADMIN
    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasRole('superadmin');
    }

    // ⛔ RESOURCE INI CUMA BISA DIAKSES SUPERADMIN
    public static function canViewAny(): bool
    {
        return auth()->user()?->hasRole('superadmin');
    }
}
