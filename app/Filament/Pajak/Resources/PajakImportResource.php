<?php

namespace App\Filament\Pajak\Resources;

use App\Filament\Pajak\Resources\PajakImportResource\Pages;
use App\Filament\Pajak\Resources\PajakImportResource\RelationManagers;
use App\Models\PajakImport;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class PajakImportResource extends Resource
{
    protected static ?string $model = PajakImport::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-up-tray';
    protected static ?string $navigationLabel = 'Faktur Pajak Coretax';
    protected static ?string $pluralModelLabel = 'Faktur Pajak Coretax';
    protected static ?string $modelLabel = 'Faktur Pajak Coretax';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Penjual')
                    ->schema([
                        Forms\Components\TextInput::make('npwp_penjual')->label('NPWP Penjual'),
                        Forms\Components\TextInput::make('nama_penjual')->label('Nama Penjual'),
                    ])->columns(2),

                Forms\Components\Section::make('Informasi Faktur')
                    ->schema([
                        Forms\Components\TextInput::make('nomor_faktur_pajak')->label('Nomor Faktur Pajak'),
                        Forms\Components\DatePicker::make('tanggal_faktur_pajak')->label('Tanggal Faktur Pajak'),
                        Forms\Components\TextInput::make('masa_pajak')->label('Masa Pajak'),
                        Forms\Components\TextInput::make('tahun')->label('Tahun'),
                        Forms\Components\TextInput::make('masa_pajak_pengkreditan')->label('Masa Pajak Pengkreditan'),
                        Forms\Components\TextInput::make('tahun_pajak_pengkreditan')->label('Tahun Pajak Pengkreditan'),
                        Forms\Components\TextInput::make('status_faktur')->label('Status Faktur'),
                    ])->columns(2),

                Forms\Components\Section::make('Nilai')
                    ->schema([
                        Forms\Components\TextInput::make('harga_jual_dpp')->label('Harga Jual/Penggantian/DPP')->numeric(),
                        Forms\Components\TextInput::make('dpp_nilai_lain')->label('DPP Nilai Lain/DPP')->numeric(),
                        Forms\Components\TextInput::make('ppn')->label('PPN')->numeric(),
                        Forms\Components\TextInput::make('ppnbm')->label('PPnBM')->numeric(),
                    ])->columns(2),

                Forms\Components\Section::make('Informasi Lain')
                    ->schema([
                        Forms\Components\TextInput::make('perekam')->label('Perekam'),
                        Forms\Components\TextInput::make('referensi')->label('Referensi'),
                        Forms\Components\TextInput::make('nomor_sp2d')->label('Nomor SP2D'),
                        Forms\Components\TextInput::make('valid')->label('Valid'),
                        Forms\Components\TextInput::make('dilaporkan')->label('Dilaporkan'),
                        Forms\Components\TextInput::make('dilaporkan_oleh_penjual')->label('Dilaporkan oleh Penjual'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('npwp_penjual')
                    ->label('NPWP Penjual')
                    ->searchable(),
                Tables\Columns\TextColumn::make('nama_penjual')
                    ->label('Nama Penjual')
                    ->searchable(),
                Tables\Columns\TextColumn::make('nomor_faktur_pajak')
                    ->label('Nomor Faktur Pajak')
                    ->copyable()
                    ->copyMessage('Nomor Faktur Pajak berhasil disalin')
                    ->searchable(),
                Tables\Columns\TextColumn::make('tanggal_faktur_pajak')
                    ->label('Tanggal Faktur Pajak')
                    ->date(),
                Tables\Columns\TextColumn::make('masa_pajak')
                    ->label('Masa Pajak'),
                Tables\Columns\TextColumn::make('tahun')
                    ->label('Tahun'),
                Tables\Columns\TextColumn::make('masa_pajak_pengkreditan')
                    ->label('Masa Pajak Pengkreditan'),
                Tables\Columns\TextColumn::make('tahun_pajak_pengkreditan')
                    ->label('Tahun Pajak Pengkreditan'),
                Tables\Columns\TextColumn::make('status_faktur')
                    ->label('Status Faktur')
                    ->badge(),
                Tables\Columns\TextColumn::make('harga_jual_dpp')
                    ->label('Harga Jual/Penggantian/DPP')
                    ->numeric(decimalPlaces: 2),
                Tables\Columns\TextColumn::make('dpp_nilai_lain')
                    ->label('DPP Nilai Lain/DPP')
                    ->numeric(decimalPlaces: 2),
                Tables\Columns\TextColumn::make('ppn')
                    ->label('PPN')
                    ->numeric(decimalPlaces: 2),
                Tables\Columns\TextColumn::make('ppnbm')
                    ->label('PPnBM')
                    ->numeric(decimalPlaces: 2),
                Tables\Columns\TextColumn::make('perekam')
                    ->label('Perekam'),
                Tables\Columns\TextColumn::make('referensi')
                    ->label('Referensi')
                    ->searchable(),
                Tables\Columns\TextColumn::make('nomor_sp2d')
                    ->label('Nomor SP2D'),
                Tables\Columns\TextColumn::make('valid')
                    ->label('Valid')
                    ->formatStateUsing(function ($state) {
                        return $state ? 'TRUE' : 'FALSE';
                    }),

                Tables\Columns\TextColumn::make('dilaporkan')
                    ->label('Dilaporkan')
                    ->formatStateUsing(function ($state) {
                        return $state ? 'TRUE' : 'FALSE';
                    }),

                Tables\Columns\TextColumn::make('dilaporkan_oleh_penjual')
                    ->label('Dilaporkan oleh Penjual')
                    ->formatStateUsing(function ($state) {
                        return $state ? 'TRUE' : 'FALSE';
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tahun')
                    ->label('Tahun')
                    ->options(
                        fn() => PajakImport::query()
                            ->whereNotNull('tahun')
                            ->distinct()
                            ->pluck('tahun', 'tahun')
                            ->toArray()
                    ),
                Tables\Filters\SelectFilter::make('masa_pajak')
                    ->label('Masa Pajak')
                    ->options(
                        fn() => PajakImport::query()
                            ->whereNotNull('masa_pajak')
                            ->distinct()
                            ->pluck('masa_pajak', 'masa_pajak')
                            ->toArray()
                    ),
            ])
            ->headerActions([
                Tables\Actions\Action::make('importExcel')
                    ->label('Import Excel')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('success')
                    ->form([
                        Forms\Components\FileUpload::make('attachment')
                            ->required()
                            ->label('File Excel (.xlsx)')
                            ->disk('local')
                            ->directory('imports')
                            ->acceptedFileTypes([
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                'application/vnd.ms-excel',
                                'text/csv',
                            ]),
                    ])
                    ->action(function (array $data) {
                        \Maatwebsite\Excel\Facades\Excel::import(
                            new \App\Imports\PajakImportClass,
                            $data['attachment'],
                            'local',
                        );

                        \Filament\Notifications\Notification::make()
                            ->title('Data Faktur Pajak berhasil diimport!')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('deleteAll')
                    ->label('Hapus Semua Data')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Hapus Semua Data?')
                    ->modalDescription('Semua data Faktur Pajak Coretax akan dihapus permanen. Tindakan ini tidak dapat dibatalkan.')
                    ->action(fn() => PajakImport::truncate())
                    ->visible(fn() => Auth::user()->hasRole('superadmin')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->slideOver()->modalWidth('screen'),
                Tables\Actions\DeleteAction::make()->visible(fn() => Auth::user()->hasRole('superadmin')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->visible(fn() => Auth::user()->hasRole('superadmin')),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManagePajakImports::route('/'),
        ];
    }
}
