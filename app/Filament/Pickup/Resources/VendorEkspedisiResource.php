<?php

namespace App\Filament\Pickup\Resources;

use App\Filament\Pickup\Resources\VendorEkspedisiResource\Pages;
use App\Models\Sap\SapVendorEkspedisi;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class VendorEkspedisiResource extends Resource
{
    protected static ?string $model = SapVendorEkspedisi::class;

    protected static ?string $navigationIcon  = 'heroicon-o-truck';
    protected static ?string $navigationLabel = 'Vendor Ekspedisi (VE)';
    protected static ?string $modelLabel      = 'Vendor Ekspedisi';
    protected static ?string $pluralLabel     = 'Vendor Ekspedisi';
    protected static ?string $navigationGroup = 'Master SAP';
    protected static ?int $navigationSort     = 1;

    /**
     * Hanya superadmin yang lihat di navigasi.
     */
    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();
        return (bool) $user?->hasRole('superadmin');
    }

    /**
     * Gate Filament: hanya superadmin boleh akses list/lihat.
     */
    public static function canViewAny(): bool
    {
        $user = auth()->user();
        return (bool) $user?->hasRole('superadmin');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function canForceDelete($record): bool
    {
        return false;
    }

    public static function canForceDeleteAny(): bool
    {
        return false;
    }

    public static function canRestore($record): bool
    {
        return false;
    }

    public static function canRestoreAny(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Vendor Ekspedisi (SAP OCRD)')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('CardCode')->label('Kode BP')->disabled(),
                    Forms\Components\TextInput::make('CardName')->label('Nama')->disabled(),

                    Forms\Components\TextInput::make('GroupName')->label('Group')->disabled(),
                    Forms\Components\TextInput::make('LicTradNum')->label('NPWP')->disabled(),

                    Forms\Components\TextInput::make('Phone1')->label('Telp 1')->disabled(),
                    Forms\Components\TextInput::make('Phone2')->label('Telp 2')->disabled(),

                    Forms\Components\TextInput::make('Cellular')->label('HP')->disabled(),
                    Forms\Components\TextInput::make('E_Mail')->label('Email')->disabled(),

                    Forms\Components\TextInput::make('IntrntSite')->label('Website')->disabled(),
                    Forms\Components\TextInput::make('CntctPrsn')->label('Contact Person')->disabled(),

                    Forms\Components\Textarea::make('Address')->label('Alamat')->rows(3)->columnSpanFull()->disabled(),

                    Forms\Components\TextInput::make('City')->label('Kota')->disabled(),
                    Forms\Components\TextInput::make('County')->label('Provinsi/County')->disabled(),

                    Forms\Components\TextInput::make('ZipCode')->label('Kode Pos')->disabled(),
                    Forms\Components\TextInput::make('Country')->label('Negara')->disabled(),

                    Forms\Components\TextInput::make('Currency')->label('Currency')->disabled(),
                    Forms\Components\TextInput::make('Balance')->label('Balance')->disabled(),

                    Forms\Components\Textarea::make('Notes')->label('Notes')->rows(3)->columnSpanFull()->disabled(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('CardCode', 'asc')
            ->columns([
                Tables\Columns\TextColumn::make('CardCode')
                    ->label('Kode')
                    ->searchable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('CardName')
                    ->label('Nama Vendor')
                    ->searchable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('GroupName')
                    ->label('Group')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('Phone1')
                    ->label('Telp')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('Cellular')
                    ->label('HP')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('E_Mail')
                    ->label('Email')
                    ->toggleable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('City')
                    ->label('Kota')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('UpdateDate')
                    ->label('Update')
                    ->toggleable(),
            ])
            ->filters([
                // optional: kalau mau filter tambahan, bisa ditambah di sini
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                // read-only
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        // tetap pakai query default Sushi (data hasil fetch SAP)
        return parent::getEloquentQuery();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVendorEkspedisis::route('/'),
            'view'  => Pages\ViewVendorEkspedisi::route('/{record}'),
        ];
    }
}
