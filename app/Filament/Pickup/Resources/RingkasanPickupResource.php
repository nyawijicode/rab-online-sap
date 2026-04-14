<?php

namespace App\Filament\Pickup\Resources;

use App\Filament\Pickup\Resources\RingkasanPickupResource\Pages;
use App\Models\Pickup;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use App\Models\Company;
use Illuminate\Support\Carbon;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\PickupExport;
use App\Exports\PickupFilteredExport;
use Filament\Tables\Filters\TrashedFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Actions\ActionGroup as ActionsActionGroup;
use Illuminate\Support\HtmlString;

class RingkasanPickupResource extends Resource
{
    protected static ?string $model = Pickup::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Ringkasan Pickup';
    protected static ?string $modelLabel = 'Ringkasan Pickup';
    protected static ?string $pluralModelLabel = 'Ringkasan Pickup';
    protected static ?string $navigationGroup = 'Pickup';
    protected static ?int $navigationSort = 2;

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
            ->defaultSort('id', 'desc')
            ->poll('5s')
            ->columns([
                Tables\Columns\TextColumn::make('tagihan_ke')
                    ->label('Pick Up')
                    ->formatStateUsing(fn($state) => new HtmlString('<b>' . e(strtoupper($state ?? '-')) . '</b>'))
                    ->description(fn(Pickup $record) => $record->alamat_ambil ?: '-')
                    ->wrap()
                    ->searchable(),

                Tables\Columns\TextColumn::make('cabang_pic_name')
                    ->label('PIC Pengirim / No HP')
                    ->state(function (Pickup $record) {
                        return ($record->cabang_pic_name ?: $record->vendor_pic_name) ?: '-';
                    })
                    ->description(fn(Pickup $record) => ($record->cabang_pic_phone ?: $record->vendor_pic_phone) ?: '-')
                    ->wrap()
                    ->searchable(),

                Tables\Columns\TextColumn::make('customer_name')
                    ->label('Kirim Ke')
                    ->state(function (Pickup $record) {
                        return ($record->customer_name ?: $record->vendor_name) ?: '-';
                    })
                    ->description(fn(Pickup $record) => $record->tujuan_pengiriman ?: '-')
                    ->wrap()
                    ->searchable(),

                Tables\Columns\TextColumn::make('penerima_pic_name')
                    ->label('PIC Penerima / No HP')
                    ->state(function (Pickup $record) {
                        return ($record->penerima_pic_name ?: $record->vendor_pic_name) ?: '-';
                    })
                    ->description(fn(Pickup $record) => ($record->penerima_pic_phone ?: $record->vendor_pic_phone) ?: '-')
                    ->wrap()
                    ->searchable(),

                Tables\Columns\TextColumn::make('package_id')
                    ->label('Keterangan')
                    ->formatStateUsing(function (Pickup $record) {
                        // Tentukan ID paket prioritas
                        if ($record->id_paket) {
                            $idPaket = $record->id_paket;
                        } elseif ($record->package_id) {
                            $idPaket = $record->package_id;
                        } elseif ($record->no_resi) {
                            $idPaket = $record->no_resi;
                        } else {
                            $idPaket = $record->po_number;
                        }

                        // Tampilkan huruf besar dan bold, default "-" jika kosong
                        return new HtmlString('<b>' . e(strtoupper($idPaket ?? '-')) . '</b>');
                    })
                    ->description(function (Pickup $record) {
                        // Ambil semua item dan kelompokkan berdasarkan satuan
                        $items = $record->items()
                            ->selectRaw('unit, sum(pickup_quantity) as total')
                            ->groupBy('unit')
                            ->get();

                        if ($items->isEmpty()) return 'Qty : 0';

                        return $items->map(function ($item) {
                            return (int) $item->total . ' ' . strtoupper($item->unit ?: 'KOLI');
                        })->join(', ');
                    })
                    ->wrap()
                    ->searchable(),
                Tables\Columns\TextColumn::make('notes')
                    ->label('Catatan')
                    ->searchable()
                    ->copyable()
                    ->limit(50)
                    ->wrap()
                    ->tooltip(fn($record) => $record->notes)
                    ->searchable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'warning' => 'scheduled',
                        'info'    => 'shipped',
                        'success' => 'completed',
                        'danger'  => 'canceled',
                    ])
                    ->formatStateUsing(fn($state) => match ($state) {
                        'scheduled' => 'Scheduled',
                        'shipped'   => 'Shipped',
                        'completed' => 'Completed',
                        'canceled'  => 'Canceled',
                        default     => $state,
                    }),
            ])
            ->filtersFormColumns(2)
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'scheduled' => 'Scheduled',
                        'shipped'   => 'Shipped',
                        'completed' => 'Completed',
                        'canceled'  => 'Canceled',
                    ]),
                Tables\Filters\SelectFilter::make('perusahaan_id')
                    ->label('Perusahaan')
                    ->options(fn() => Company::query()->orderBy('nama_perusahaan')->pluck('nama_perusahaan', 'id')->all()),
                Tables\Filters\SelectFilter::make('cabang_id')
                    ->label('Cabang')
                    ->relationship('cabang', 'kode')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('kota')
                    ->label('Filter Kota')
                    ->options(function () {
                        return Pickup::query()
                            ->whereNotNull('kota')
                            ->where('kota', '!=', '')
                            ->distinct()
                            ->orderBy('kota')
                            ->pluck('kota', 'kota')
                            ->toArray();
                    })
                    ->searchable()
                    ->preload(),
                Tables\Filters\Filter::make('pickup_date')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('Mulai Tanggal'),
                        Forms\Components\DatePicker::make('until')->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('pickup_date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('pickup_date', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['from'] ?? null) {
                            $indicators[] = 'Pickup dari: ' . Carbon::parse($data['from'])->format('d M Y');
                        }
                        if ($data['until'] ?? null) {
                            $indicators[] = 'Pickup sampai: ' . Carbon::parse($data['until'])->format('d M Y');
                        }
                        return $indicators;
                    }),
                Tables\Filters\TrashedFilter::make()
                    ->visible(fn() => auth()->user()?->hasRole('superadmin')),
            ])
            ->headerActions([
                Tables\Actions\Action::make('download_all')
                    ->label('Download All')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('info')
                    ->action(fn() => Excel::download(new PickupExport(), 'pickup_all_' . date('Ymd_His') . '.xlsx')),

                Tables\Actions\Action::make('download_filter')
                    ->label('Download Filter')
                    ->icon('heroicon-o-funnel')
                    ->color('success')
                    ->action(function ($livewire) {
                        return Excel::download(
                            new PickupFilteredExport(
                                $livewire->tableFilters,
                                $livewire->tableSearch
                            ),
                            'pickup_filtered_' . date('Ymd_His') . '.xlsx'
                        );
                    }),
            ])
            ->actions([
                ActionsActionGroup::make([
                    Tables\Actions\Action::make('lihat_detail')
                        ->label('Lihat Detail')
                        ->icon('heroicon-o-eye')
                        ->modalHeading('Preview Detail Pickup')
                        ->modalWidth('4xl')
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('Tutup')
                        ->modalContent(fn(Pickup $record) => view('filament.pickup.ringkasan-detail', ['record' => $record])),

                ]),
            ])
            ->bulkActions([
                // Tables\Actions\BulkActionGroup::make([
                //     Tables\Actions\DeleteBulkAction::make(),
                // ]),
            ])->actionsPosition(\Filament\Tables\Enums\ActionsPosition::BeforeColumns);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->withoutGlobalScopes([
                \Illuminate\Database\Eloquent\SoftDeletingScope::class,
            ]);

        if (!auth()->user()->hasAnyRole(['superadmin', 'logistik', 'purchasing'])) {
            $query->where(function ($q) {
                $q->where('created_by', auth()->id())
                    ->orWhere('cabang_pic_user_id', auth()->id());
            });
        }

        return $query;
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRingkasanPickups::route('/'),
        ];
    }
}
