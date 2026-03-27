<?php

namespace App\Filament\Pajak\Resources;

use App\Filament\Pajak\Resources\CocokanFakturPajakResource\Pages;
use App\Models\CocokanFakturPajak;
use App\Services\CocokanFakturPajakService;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class CocokanFakturPajakResource extends Resource
{
    protected static ?string $model = CocokanFakturPajak::class;

    protected static ?string $navigationIcon = 'heroicon-o-scale';
    protected static ?string $navigationLabel = 'Cocokan Faktur Pajak';
    protected static ?string $pluralModelLabel = 'Cocokan Faktur Pajak';
    protected static ?string $modelLabel = 'Cocokan Faktur Pajak';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('row_number')
                    ->label('No')
                    ->rowIndex(),
                Tables\Columns\TextColumn::make('nomor_faktur')
                    ->label('Nomor Faktur Pajak')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Nomor Faktur Pajak berhasil disalin'),
                Tables\Columns\TextColumn::make('nama_vendor')
                    ->label('Nama Vendor')
                    ->searchable()

                    ->wrap(),
                Tables\Columns\TextColumn::make('muncul_minggu')
                    ->label('Muncul (Minggu)')
                    ->state(fn(CocokanFakturPajak $record): int => $record->hitungMunculMinggu())
                    ->alignCenter()
                    ->badge()
                    ->color(fn(int $state): string => match (true) {
                        $state >= 4 => 'danger',
                        $state >= 2 => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\IconColumn::make('ada_di_coretax')
                    ->label('Di Coretax')
                    ->boolean()
                    ->alignCenter(),
                Tables\Columns\IconColumn::make('ada_di_sap')
                    ->label('Di SAP')
                    ->boolean()
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('status_cocok')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn(bool $state): string => $state ? 'TRUE' : 'FALSE')
                    ->color(fn(bool $state): string => $state ? 'success' : 'danger')
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('first_appeared_at')
                    ->label('Muncul (Coretax)')
                    ->date('d M Y')
                    ->searchable(),
                Tables\Columns\TextColumn::make('resolved_at')
                    ->label('Tgl Cocok')
                    ->date('d M Y')
                    ->searchable()
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('rentang_tanggal')
                    ->label('Rentang Tanggal')
                    ->searchable()
                    ->getStateUsing(fn(CocokanFakturPajak $record) => $record->periode_minggu)
                    ->formatStateUsing(function (?string $state): ?string {
                        if (!$state || !str_contains(strtoupper($state), '-W')) return $state;

                        $parts = explode('-W', strtoupper($state));
                        if (count($parts) !== 2) return $state;

                        $year = (int) $parts[0];
                        $week = (int) $parts[1];

                        $start = \Carbon\Carbon::now()->setISODate($year, $week)->startOfWeek();
                        $end = $start->copy()->endOfWeek();

                        if ($start->month === $end->month) {
                            return $start->format('d') . ' - ' . $end->translatedFormat('d F Y');
                        } elseif ($start->year === $end->year) {
                            return $start->translatedFormat('d M') . ' - ' . $end->translatedFormat('d M Y');
                        }
                        return $start->translatedFormat('d M Y') . ' - ' . $end->translatedFormat('d M Y');
                    }),
                Tables\Columns\TextColumn::make('periode_minggu')
                    ->label('Periode Minggu')->toggleable(isToggledHiddenByDefault: true)->searchable(),
                Tables\Columns\TextColumn::make('periode_bulan')
                    ->label('Periode Bulan')->date('M Y')->toggleable(isToggledHiddenByDefault: true)->searchable(),
            ])
            ->defaultSort('status_cocok', 'asc') // FALSE first
            ->filters([

                Tables\Filters\SelectFilter::make('status_cocok')
                    ->label('Status')
                    ->options([
                        '0' => 'FALSE (Belum Cocok)',
                        '1' => 'TRUE (Sudah Cocok)',
                    ]),

                Tables\Filters\SelectFilter::make('periode_minggu')
                    ->label('Periode Minggu')
                    ->options(
                        fn() => CocokanFakturPajak::query()
                            ->distinct()
                            ->orderBy('periode_minggu', 'desc')
                            ->pluck('periode_minggu', 'periode_minggu')
                            ->toArray()
                    ),

                Tables\Filters\SelectFilter::make('periode_bulan')
                    ->label('Periode Bulan')
                    ->options(
                        fn() => CocokanFakturPajak::query()
                            ->distinct()
                            ->orderBy('periode_bulan', 'desc')
                            ->pluck('periode_bulan', 'periode_bulan')
                            ->toArray()
                    ),
                Tables\Filters\SelectFilter::make('nama_vendor')
                    ->label('Nama Vendor')
                    ->options(
                        fn() => CocokanFakturPajak::query()
                            ->select('nama_vendor')
                            ->whereNotNull('nama_vendor')
                            ->distinct()
                            ->orderBy('nama_vendor')
                            ->pluck('nama_vendor', 'nama_vendor')
                            ->toArray()
                    )
                    ->searchable(),
                /*
    |--------------------------------------------------------------------------
    | 🔥 FILTER TANGGAL MUNCUL (UI RAPI)
    |--------------------------------------------------------------------------
    */
                Tables\Filters\Filter::make('tanggal_muncul_range')
                    ->label('Tanggal Muncul')
                    ->form([
                        Section::make('Range Tanggal Muncul')
                            ->schema([
                                Grid::make(2)->schema([
                                    DatePicker::make('dari')
                                        ->label('Dari Tanggal'),

                                    DatePicker::make('sampai')
                                        ->label('Sampai Tanggal'),
                                ]),
                            ]),
                    ])
                    ->query(function ($query, array $data) {

                        if (!$data['dari'] && !$data['sampai']) {
                            return $query;
                        }

                        return $query
                            ->whereNotNull('first_appeared_at')
                            ->when(
                                $data['dari'] && $data['sampai'],
                                fn($q) => $q->whereBetween('first_appeared_at', [$data['dari'], $data['sampai']])
                            )
                            ->when(
                                $data['dari'] && !$data['sampai'],
                                fn($q) => $q->whereDate('first_appeared_at', '>=', $data['dari'])
                            )
                            ->when(
                                !$data['dari'] && $data['sampai'],
                                fn($q) => $q->whereDate('first_appeared_at', '<=', $data['sampai'])
                            );
                    }),

                /*
    |--------------------------------------------------------------------------
    | 🔥 FILTER TANGGAL COCOK (UI RAPI)
    |--------------------------------------------------------------------------
    */
                Tables\Filters\Filter::make('tanggal_cocok_range')
                    ->label('Tanggal Cocok')
                    ->form([
                        Section::make('Range Tanggal Cocok')
                            ->schema([
                                Grid::make(2)->schema([
                                    DatePicker::make('dari')
                                        ->label('Dari Tanggal'),

                                    DatePicker::make('sampai')
                                        ->label('Sampai Tanggal'),
                                ]),
                            ]),
                    ])
                    ->query(function ($query, array $data) {

                        if (!$data['dari'] && !$data['sampai']) {
                            return $query;
                        }

                        return $query
                            ->whereNotNull('resolved_at')
                            ->when(
                                $data['dari'] && $data['sampai'],
                                fn($q) => $q->whereBetween('resolved_at', [$data['dari'], $data['sampai']])
                            )
                            ->when(
                                $data['dari'] && !$data['sampai'],
                                fn($q) => $q->whereDate('resolved_at', '>=', $data['dari'])
                            )
                            ->when(
                                !$data['dari'] && $data['sampai'],
                                fn($q) => $q->whereDate('resolved_at', '<=', $data['sampai'])
                            );
                    }),

            ])
            ->filtersFormColumns(2)
            ->headerActions([
                Tables\Actions\Action::make('jalankanPencocokan')
                    ->label('Jalankan Pencocokan')
                    ->icon('heroicon-o-arrow-path')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Jalankan Pencocokan?')
                    ->modalDescription('Sistem akan mencocokkan Nomor Faktur Pajak antara data Coretax (yang diupload) dengan data A/P Invoice dari SAP. Proses ini mungkin memerlukan beberapa saat.')
                    ->action(function () {
                        $service = new CocokanFakturPajakService();
                        $result = $service->jalankanPencocokan();

                        \Filament\Notifications\Notification::make()
                            ->title('Pencocokan Selesai!')
                            ->body("Total: {$result['total']} | TRUE: {$result['true']} | FALSE: {$result['false']} | Baru: {$result['baru']}")
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('resetData')
                    ->label('Reset Data Cocokan')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Reset Semua Data Cocokan?')
                    ->modalDescription('Semua data hasil pencocokan akan dihapus. Anda perlu menjalankan pencocokan ulang. Tindakan ini tidak dapat dibatalkan.')
                    ->action(fn() => CocokanFakturPajak::truncate())
                    ->visible(fn() => Auth::user()->hasRole('superadmin')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->slideOver()->modalWidth('screen'),
            ])
            ->bulkActions([]);
    }

    public static function infolist(\Filament\Infolists\Infolist $infolist): \Filament\Infolists\Infolist
    {
        return $infolist
            ->schema([
                \Filament\Infolists\Components\Section::make('Detail Pencocokan')
                    ->schema([
                        \Filament\Infolists\Components\TextEntry::make('nomor_faktur')->label('Nomor Faktur Pajak'),
                        \Filament\Infolists\Components\TextEntry::make('nama_vendor')->label('Nama Vendor'),
                        \Filament\Infolists\Components\IconEntry::make('ada_di_coretax')->label('Ada di Coretax')->boolean(),
                        \Filament\Infolists\Components\IconEntry::make('ada_di_sap')->label('Ada di SAP')->boolean(),
                        \Filament\Infolists\Components\TextEntry::make('status_cocok')
                            ->label('Status')
                            ->badge()
                            ->formatStateUsing(fn(bool $state): string => $state ? 'TRUE' : 'FALSE')
                            ->color(fn(bool $state): string => $state ? 'success' : 'danger'),
                        \Filament\Infolists\Components\TextEntry::make('first_appeared_at')->label('Pertama Muncul')->date(),
                        \Filament\Infolists\Components\TextEntry::make('resolved_at')->label('Tanggal Cocok')->date()->placeholder('-'),
                        \Filament\Infolists\Components\TextEntry::make('periode_minggu')->label('Periode Minggu'),
                        \Filament\Infolists\Components\TextEntry::make('periode_bulan')->label('Periode Bulan'),
                    ])
                    ->columns(['default' => 2]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageCocokanFakturPajaks::route('/'),
        ];
    }
}
