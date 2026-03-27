<?php

namespace App\Filament\Pickup\Resources;

use App\Filament\Pickup\Resources\PickupResource\Pages;
use App\Models\Pickup;
use App\Models\Company;
use App\Services\Sap\SapHanaService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\ActionGroup as ActionsActionGroup;
use Filament\Tables\Table;
use Illuminate\Validation\Rule;
use Illuminate\Support\Carbon;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Filters\TrashedFilter;

class PickupResource extends Resource
{
    protected static ?string $model = Pickup::class;

    protected static ?string $navigationIcon  = 'heroicon-o-truck';
    protected static ?string $navigationLabel = 'Pickup';
    protected static ?string $modelLabel      = 'Pickup';
    protected static ?string $pluralLabel     = 'Pickup';
    protected static ?string $navigationGroup = 'Pickup';
    protected static ?int $navigationSort     = 1;

    /**
     * Helper: ambil mode perusahaan dari companies.kode (sap/ssm)
     */
    public static function getCompanyMode(?int $companyId): string
    {
        if (! $companyId) return 'ssm';

        $company = Company::query()->select(['id', 'kode'])->find($companyId);

        $kode = strtolower((string) ($company?->kode ?? 'ssm'));

        return in_array($kode, ['sap', 'ssm'], true) ? $kode : 'ssm';
    }

    /**
     * Helper: hitung apakah PO sudah terpenuhi qty-nya.
     *
     * Return array: [
     *   'po_total_qty'     => float,   // total qty dari SAP (semua line)
     *   'pickup_total_qty' => float,   // total qty pickup aktif (non-canceled)
     *   'is_fulfilled'     => bool,    // true jika pickup_total_qty >= po_total_qty
     * ]
     */
    public static function getPoQtyFulfillment(int $docEntry, ?int $excludePickupId = null): array
    {
        $sap   = app(SapHanaService::class);
        $lines = $sap->getPurchaseOrderLines($docEntry);

        $poTotalQty = collect($lines)->sum(fn($l) => (float) ($l['Quantity'] ?? 0));

        $pickupTotalQty = \App\Models\PickupItem::query()
            ->whereHas('pickup', function ($q) use ($docEntry, $excludePickupId) {
                $q->where('po_docentry', $docEntry)
                    ->whereNotIn('status', ['canceled'])
                    ->when($excludePickupId, fn($q) => $q->where('id', '!=', $excludePickupId));
            })
            ->sum('pickup_quantity');

        return [
            'po_total_qty'     => $poTotalQty,
            'pickup_total_qty' => (float) $pickupTotalQty,
            'is_fulfilled'     => $poTotalQty > 0 && (float) $pickupTotalQty >= $poTotalQty,
        ];
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            // =========================
            // 0) PERUSAHAAN
            // =========================
            Forms\Components\Section::make('Perusahaan')
                ->columns(1)
                ->schema([
                    Forms\Components\Select::make('perusahaan_id')
                        ->label('Perusahaan')
                        ->required()
                        ->searchable()
                        ->default(3)
                        ->preload()
                        ->options(
                            fn() => Company::query()
                                ->orderBy('nama_perusahaan')
                                ->pluck('nama_perusahaan', 'id')
                                ->all()
                        )
                        ->reactive()
                        ->afterStateUpdated(function ($state, Set $set) {
                            // reset saat perusahaan berubah biar ga nyampur SAP/SSM
                            $set('po_docentry', null);
                            $set('po_number', null);
                            $set('vendor_code', null);
                            $set('vendor_name', null);
                            $set('vendor_address', null);
                            $set('package_id', null);

                            $set('expedition_supplier_code', null);
                            $set('expedition_supplier_name', null);

                            $set('items', []);

                            // Otomatis isi Tagihan Ke (bisa diedit manual nanti)
                            if ($state) {
                                $mode = strtoupper(static::getCompanyMode((int) $state));
                                $set('tagihan_ke', $mode);
                            }
                        }),
                ]),

            // =========================
            // 1) PO & Vendor (SAP MODE)
            // =========================
            Forms\Components\Section::make('PO & Vendor (SAP)')
                ->columns(2)
                ->visible(fn(Get $get) => static::getCompanyMode((int) $get('perusahaan_id')) === 'sap')
                ->schema([
                    Forms\Components\Select::make('po_docentry')
                        ->label('Purchase Order (SAP OPOR)')
                        ->required(fn(Get $get) => static::getCompanyMode((int) $get('perusahaan_id')) === 'sap')
                        ->searchable()
                        ->preload()
                        ->options(function (Get $get, $record) {
                            $sap = app(SapHanaService::class);

                            // ID pickup yang sedang diedit (null saat create)
                            $currentPickupId = optional($record)->id ?? request()->route('record');

                            return collect($sap->getPurchaseOrders(statusPickup2: 'Y'))
                                ->filter(function ($r) use ($currentPickupId) {
                                    $docEntry = (int) $r['DocEntry'];
                                    if ($docEntry <= 0) return true;

                                    // Selalu tampilkan PO yang sedang dipilih di edit mode
                                    if ($currentPickupId) {
                                        $currentPo = \App\Models\Pickup::find($currentPickupId)?->po_docentry;
                                        if ((int) $currentPo === $docEntry) return true;
                                    }

                                    try {
                                        $fulfillment = static::getPoQtyFulfillment($docEntry, $currentPickupId ? (int) $currentPickupId : null);
                                        // Sembunyikan PO yang sudah terpenuhi
                                        return ! $fulfillment['is_fulfilled'];
                                    } catch (\Throwable $e) {
                                        return true;
                                    }
                                })
                                ->mapWithKeys(fn($r) => [
                                    (int) $r['DocEntry'] => ($r['DocNum'] ?? '-') . ' — ' . ($r['CardName'] ?? '-'),
                                ])
                                ->all();
                        })
                        ->reactive()
                        ->afterStateUpdated(function ($state, Forms\Set $set, Get $get) {
                            if (static::getCompanyMode((int) $get('perusahaan_id')) !== 'sap') {
                                return;
                            }

                            if (! $state) {
                                $set('po_number', null);
                                $set('vendor_code', null);
                                $set('vendor_name', null);
                                $set('vendor_address', null);
                                $set('package_id', null);
                                $set('items', []);
                                return;
                            }

                            $sap = app(SapHanaService::class);

                            $detail = $sap->getPurchaseOrderDetail((int) $state);
                            $header = $detail['header'] ?? null;
                            $lines  = $detail['lines'] ?? [];

                            if (! $header) {
                                return;
                            }

                            $set('po_number', $header['DocNum'] ?? null);
                            $set('vendor_code', $header['CardCode'] ?? null);
                            $set('vendor_name', $header['CardName'] ?? null);
                            $set('package_id', $sap->getPurchaseOrderPackageId((int) $state));

                            $vendor = $sap->getVendorByCode((string) ($header['CardCode'] ?? ''));

                            if ($vendor) {
                                $fullAddress = trim(implode(', ', array_filter([
                                    $vendor['Address']  ?? null,
                                    $vendor['ZipCode'] ?? null,
                                    $vendor['City']    ?? null,
                                    $vendor['Country'] ?? null,
                                ])));

                                $set('vendor_address', $fullAddress);
                            }

                            // Otomatis isi item
                            $mappedItems = collect($lines)->map(function ($line) {
                                return [
                                    'item_code'        => $line['ItemCode'] ?? null,
                                    'item_code_sap'    => $line['ItemCode'] ?? null,
                                    'description'      => $line['Dscription'] ?? null,
                                    'po_quantity'      => $line['Quantity'] ?? null,
                                    'pickup_quantity'  => $line['Quantity'] ?? null,
                                    'line_num'         => $line['LineNum'] ?? null,
                                ];
                            })->all();

                            $set('items', $mappedItems);
                        })
                        ->rules([
                            function (?Pickup $record) {
                                return function (string $attribute, $value, \Closure $fail) use ($record) {
                                    if ($value === null || $value === '') return;

                                    $docEntry = (int) $value;

                                    try {
                                        $fulfillment = static::getPoQtyFulfillment($docEntry, $record?->id);

                                        if ($fulfillment['is_fulfilled']) {
                                            $poQty     = number_format($fulfillment['po_total_qty'], 0);
                                            $pickedQty = number_format($fulfillment['pickup_total_qty'], 0);
                                            $fail("PO ini sudah terpenuhi (qty pickup {$pickedQty} dari {$poQty} sudah tercapai).");
                                        }
                                    } catch (\Throwable $e) {
                                        // Jika SAP tidak bisa dihubungi, biarkan lolos (jangan block user)
                                    }
                                };
                            },
                        ])
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('po_number')
                        ->label('PO Number (DocNum)')
                        ->disabled()
                        ->dehydrated()
                        ->required(fn(Get $get) => static::getCompanyMode((int) $get('perusahaan_id')) === 'sap'),

                    Forms\Components\TextInput::make('vendor_code')
                        ->label('Vendor Code')
                        ->disabled()
                        ->dehydrated()
                        ->required(fn(Get $get) => static::getCompanyMode((int) $get('perusahaan_id')) === 'sap'),

                    Forms\Components\TextInput::make('vendor_name')
                        ->label('Vendor Name')
                        ->disabled()
                        ->dehydrated()
                        ->required(fn(Get $get) => static::getCompanyMode((int) $get('perusahaan_id')) === 'sap'),

                    Forms\Components\TextInput::make('package_id')
                        ->label('ID Paket (dari OPOR.Comments)')
                        ->disabled()
                        ->dehydrated()
                        ->nullable(),

                    Forms\Components\Textarea::make('vendor_address')
                        ->label('Alamat Vendor')
                        ->disabled()
                        ->dehydrated()
                        ->rows(2)
                        ->columnSpanFull(),
                ]),

            // =========================
            // 1B) PO & Vendor (SSM MODE - MANUAL)
            // =========================
            Forms\Components\Section::make('PO & Vendor (SSM - Manual)')
                ->columns(2)
                ->visible(fn(Get $get) => static::getCompanyMode((int) $get('perusahaan_id')) === 'ssm')
                ->schema([
                    Forms\Components\TextInput::make('po_number')
                        ->label('PO Number / Referensi')
                        ->required(fn(Get $get) => static::getCompanyMode((int) $get('perusahaan_id')) === 'ssm')
                        ->maxLength(50),

                    Forms\Components\TextInput::make('package_id')
                        ->label('ID Paket (Manual)')
                        ->nullable()
                        ->maxLength(100),

                    Forms\Components\TextInput::make('vendor_code')
                        ->label('Vendor Code (Manual)')
                        ->nullable()
                        ->maxLength(50),

                    Forms\Components\TextInput::make('vendor_name')
                        ->label('Vendor Name (Manual)')
                        ->required(fn(Get $get) => static::getCompanyMode((int) $get('perusahaan_id')) === 'ssm')
                        ->maxLength(150),

                    Forms\Components\Textarea::make('vendor_address')
                        ->label('Alamat Vendor (Manual)')
                        ->rows(2)
                        ->nullable()
                        ->columnSpanFull(),
                ]),
            // =========================
            // 6) Pickup Barang
            // =========================
            Forms\Components\Section::make('Pickup Barang')
                ->description('Mode SAP: barang diambil dari detail PO (POR1). Mode SSM: input barang manual.')
                ->schema([
                    Forms\Components\Repeater::make('items')
                        ->label('Daftar Barang Pickup')
                        ->relationship('items')
                        ->defaultItems(0)
                        ->addActionLabel('Tambah Barang')
                        ->columns(12)
                        ->schema([
                            // =========================
                            // SAP MODE: pilih item dari PO
                            // =========================
                            Forms\Components\Select::make('item_code_sap')
                                ->label('Barang (dari PO)')
                                ->searchable()
                                ->columnSpan(5)
                                ->options(function (Get $get) {
                                    if (static::getCompanyMode((int) $get('../../perusahaan_id')) !== 'sap') return [];

                                    $docEntry = (int) ($get('../../po_docentry') ?? 0);
                                    if ($docEntry <= 0) return [];

                                    $sap = app(SapHanaService::class);
                                    $lines = $sap->getPurchaseOrderLines($docEntry);

                                    return collect($lines)
                                        ->filter(fn($r) => ! empty($r['ItemCode']))
                                        ->mapWithKeys(function ($r) {
                                            $code = (string) $r['ItemCode'];
                                            $desc = (string) ($r['Dscription'] ?? '');
                                            $qty  = (string) ($r['Quantity'] ?? '');
                                            return [$code => "{$code} — {$desc} (PO Qty: {$qty})"];
                                        })
                                        ->all();
                                })
                                ->disableOptionWhen(function (string $value, $state, Get $get) {
                                    $items = $get('../../items') ?? [];
                                    $picked = collect($items)->pluck('item_code_sap')->filter()->values()->all();
                                    return in_array($value, $picked, true) && $state !== $value;
                                })
                                ->reactive()
                                ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                    if (static::getCompanyMode((int) $get('../../perusahaan_id')) !== 'sap') return;

                                    if ($state) {
                                        $set('item_code', $state);
                                    }

                                    $docEntry = (int) ($get('../../po_docentry') ?? 0);
                                    if ($docEntry <= 0 || ! $state) {
                                        $set('description', null);
                                        $set('po_quantity', null);
                                        $set('line_num', null);
                                        return;
                                    }

                                    $sap = app(SapHanaService::class);
                                    $lines = $sap->getPurchaseOrderLines($docEntry);

                                    $row = collect($lines)->firstWhere('ItemCode', (string) $state);

                                    $set('description', $row['Dscription'] ?? null);
                                    $set('po_quantity', $row['Quantity'] ?? null);
                                    $set('line_num', $row['LineNum'] ?? null);
                                })
                                ->visible(fn(Get $get) => static::getCompanyMode((int) $get('../../perusahaan_id')) === 'sap')
                                ->dehydrated(false),

                            // =========================
                            // SSM MODE: input manual
                            // =========================
                            Forms\Components\TextInput::make('item_code_manual')
                                ->label('Kode Barang (Manual)')
                                ->maxLength(50)
                                ->columnSpan(5)
                                ->visible(fn(Get $get) => static::getCompanyMode((int) $get('../../perusahaan_id')) === 'ssm')
                                ->live()
                                ->afterStateUpdated(fn($state, Set $set) => $set('item_code', $state))
                                ->dehydrated(false),

                            Forms\Components\TextInput::make('description')
                                ->label('Deskripsi')
                                ->disabled(fn(Get $get) => static::getCompanyMode((int) $get('../../perusahaan_id')) === 'sap')
                                ->dehydrated()
                                ->columnSpan(4)
                                ->required(fn(Get $get) => static::getCompanyMode((int) $get('../../perusahaan_id')) === 'ssm'),

                            Forms\Components\TextInput::make('po_quantity')
                                ->label('Qty PO')
                                ->disabled()
                                ->dehydrated()
                                ->columnSpan(1)
                                ->visible(fn(Get $get) => static::getCompanyMode((int) $get('../../perusahaan_id')) === 'sap'),

                            Forms\Components\TextInput::make('pickup_quantity')
                                ->label('Qty Pickup')
                                ->numeric()
                                ->minValue(0)
                                ->columnSpan(2)
                                ->rules([
                                    function (Get $get) {
                                        return function (string $attribute, $value, \Closure $fail) use ($get) {
                                            if (static::getCompanyMode((int) $get('../../perusahaan_id')) !== 'sap') return;

                                            $docEntry = (int) ($get('../../po_docentry') ?? 0);
                                            if ($docEntry <= 0 || $value === null || $value === '') return;

                                            $itemCode = $get('item_code') ?? $get('item_code_sap');
                                            if (! $itemCode) return;

                                            try {
                                                $sap   = app(SapHanaService::class);
                                                $lines = $sap->getPurchaseOrderLines($docEntry);
                                                $row   = collect($lines)->firstWhere('ItemCode', (string) $itemCode);
                                                if (! $row) return;

                                                $poQty = (float) ($row['Quantity'] ?? 0);

                                                // Qty yang sudah dipickup untuk item ini di pickup LAIN (non-canceled)
                                                // Exclude pickup yang sedang diedit (edit mode)
                                                $currentPickupId = request()->route('record');

                                                $alreadyPickedQty = \App\Models\PickupItem::query()
                                                    ->where('item_code', $itemCode)
                                                    ->whereHas('pickup', function ($q) use ($docEntry, $currentPickupId) {
                                                        $q->where('po_docentry', $docEntry)
                                                            ->whereNotIn('status', ['canceled'])
                                                            ->when($currentPickupId, fn($q) => $q->where('id', '!=', $currentPickupId));
                                                    })
                                                    ->sum('pickup_quantity');

                                                $sisaQty = max(0, $poQty - (float) $alreadyPickedQty);

                                                if ((float) $value > $sisaQty && $sisaQty < $poQty) {
                                                    // Ada sebagian sudah dipickup sebelumnya
                                                    $poQtyFmt   = number_format($poQty, 0);
                                                    $alreadyFmt = number_format((float) $alreadyPickedQty, 0);
                                                    $sisaFmt    = number_format($sisaQty, 0);
                                                    $fail("Qty melebihi sisa PO. Total PO: {$poQtyFmt}, sudah dipickup sebelumnya: {$alreadyFmt}, sisa tersedia: {$sisaFmt}.");
                                                } elseif ((float) $value > $poQty) {
                                                    $poQtyFmt = number_format($poQty, 0);
                                                    $fail("Qty pickup tidak boleh melebihi qty PO ({$poQtyFmt}).");
                                                }
                                            } catch (\Throwable $e) {
                                                // SAP tidak bisa dihubungi, biarkan lolos
                                            }
                                        };
                                    },
                                ]),

                            Forms\Components\Hidden::make('item_code'),
                            Forms\Components\Hidden::make('line_num'),
                        ])
                        ->rules([
                            function (Get $get) {
                                return function (string $attribute, $value, \Closure $fail) use ($get) {
                                    // validasi unique item (hanya yang ada kodenya)
                                    $codes = collect($value)->map(function ($row) {
                                        return $row['item_code_sap'] ?? $row['item_code_manual'] ?? $row['item_code'] ?? null;
                                    })->filter()->values();

                                    if ($codes->count() !== $codes->unique()->count()) {
                                        $fail('Tidak boleh memilih barang yang sama lebih dari 1 kali.');
                                        return;
                                    }

                                    // Validasi total qty pickup vs qty PO (hanya SAP mode)
                                    if (static::getCompanyMode((int) $get('../perusahaan_id')) !== 'sap') return;

                                    $docEntry = (int) ($get('../po_docentry') ?? 0);
                                    if ($docEntry <= 0) return;

                                    try {
                                        $sap   = app(SapHanaService::class);
                                        $lines = $sap->getPurchaseOrderLines($docEntry);

                                        // Buat map: itemCode => poQty
                                        $poQtyMap = collect($lines)->keyBy('ItemCode')->map(fn($l) => (float) ($l['Quantity'] ?? 0));

                                        foreach ($value as $row) {
                                            $itemCode     = $row['item_code'] ?? $row['item_code_sap'] ?? null;
                                            $pickupQty    = (float) ($row['pickup_quantity'] ?? 0);
                                            if (! $itemCode) continue;

                                            $poQty = $poQtyMap->get($itemCode, null);
                                            if ($poQty === null) continue;

                                            // Cek total pickup di DB untuk item ini (di pickup lain, non-canceled)
                                            // Exclude pickup yang sedang diedit (edit mode)
                                            $currentPickupId = request()->route('record');

                                            $alreadyPickedQty = \App\Models\PickupItem::query()
                                                ->where('item_code', $itemCode)
                                                ->whereHas('pickup', function ($q) use ($docEntry, $currentPickupId) {
                                                    $q->where('po_docentry', $docEntry)
                                                        ->whereNotIn('status', ['canceled'])
                                                        ->when($currentPickupId, fn($q) => $q->where('id', '!=', $currentPickupId));
                                                })
                                                ->sum('pickup_quantity');

                                            $sisaQty  = max(0, $poQty - (float) $alreadyPickedQty);
                                            $totalBaru = $pickupQty;

                                            if ($totalBaru > $sisaQty && $sisaQty < $poQty) {
                                                $poFmt   = number_format($poQty, 0);
                                                $alrFmt  = number_format((float) $alreadyPickedQty, 0);
                                                $sisaFmt = number_format($sisaQty, 0);
                                                $fail("Item [{$itemCode}]: qty melebihi sisa PO. Total PO: {$poFmt}, sudah dipickup sebelumnya: {$alrFmt}, sisa: {$sisaFmt}.");
                                            } elseif ($totalBaru > $poQty) {
                                                $poFmt = number_format($poQty, 0);
                                                $fail("Item [{$itemCode}]: qty pickup ({$totalBaru}) melebihi qty PO ({$poFmt}).");
                                            }
                                        }
                                    } catch (\Throwable $e) {
                                        // SAP tidak bisa dihubungi, biarkan lolos
                                    }
                                };
                            },
                        ])
                        ->reorderable(false),
                ]),
            // =========================
            // 2) PIC Vendor (manual)
            // =========================
            Forms\Components\Section::make('Alamat & PIC Vendor')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('vendor_pic_name')
                        ->label('Nama PIC Vendor')
                        ->placeholder('Contoh: Budi')
                        ->maxLength(100)
                        ->required(),

                    Forms\Components\TextInput::make('vendor_pic_phone')
                        ->label('No HP PIC Vendor')
                        ->tel()
                        ->required()
                        ->maxLength(20)
                        ->rule('regex:/^(08|\+628)[0-9]{8,12}$/')
                        ->validationMessages([
                            'regex' => 'Format nomor HP tidak valid (gunakan 08xxxx atau +628xxxx)',
                        ]),
                    Forms\Components\TextInput::make('tagihan_ke')
                        ->label('Tagihan Ke')
                        ->placeholder('SAP / SSM / dll')
                        ->maxLength(100)
                        ->nullable(),

                    Forms\Components\TextInput::make('pengambilan_cabang')
                        ->label('Pengambilan Cabang')
                        ->maxLength(150)
                        ->nullable(),
                    Forms\Components\TextInput::make('kota')
                        ->label('Kota')
                        ->maxLength(100)
                        ->nullable(),

                    Forms\Components\Textarea::make('alamat_ambil')
                        ->label('Alamat Ambil')
                        ->rows(3)
                        ->nullable(),

                    Forms\Components\TextInput::make('tujuan_pengiriman')
                        ->label('Tujuan Pengiriman')
                        ->maxLength(100)
                        ->nullable(),

                    Forms\Components\Textarea::make('alamat_dropship')
                        ->label('Alamat Dropship')
                        ->rows(3)
                        ->nullable(),
                ]),

            // =========================
            // 3) Jadwal Pickup
            // =========================
            Forms\Components\Section::make('Jadwal Pickup')
                ->columns(3)
                ->schema([
                    Forms\Components\DatePicker::make('pickup_date')
                        ->label('Pickup Date')

                        ->reactive()
                        ->afterStateUpdated(function ($state, Forms\Set $set) {
                            if (! $state) {
                                $set('pickup_day', null);
                                return;
                            }
                            $set('pickup_day', Carbon::parse($state)->translatedFormat('l'));
                        })
                        ->columnSpan(1),

                    Forms\Components\TextInput::make('pickup_day')
                        ->label('Pickup Day')
                        ->disabled()
                        ->dehydrated()
                        ->columnSpan(1),

                    Forms\Components\TextInput::make('pickup_duration')
                        ->label('Estimasi Durasi (hari)')
                        ->numeric()
                        ->minValue(1)
                        ->nullable()
                        ->columnSpan(1),
                ]),

            // =========================
            // 4) Ekspedisi
            // =========================
            Forms\Components\Section::make('Ekspedisi')
                ->columns(2)
                ->schema([
                    // SAP mode: pilih dari SAP
                    Forms\Components\Select::make('expedition_supplier_code')
                        ->label('Supplier Ekspedisi (SAP)')
                        ->nullable()
                        ->searchable()
                        ->preload()
                        ->options(function (Get $get) {
                            if (static::getCompanyMode((int) $get('perusahaan_id')) !== 'sap') return [];

                            $sap = app(SapHanaService::class);

                            if (method_exists($sap, 'getVendorsEkspedisi')) {
                                return collect($sap->getVendorsEkspedisi())
                                    ->mapWithKeys(fn($r) => [
                                        (string) $r['CardCode'] => (string) $r['CardCode'] . ' — ' . (string) ($r['CardName'] ?? ''),
                                    ])
                                    ->all();
                            }

                            return [];
                        })
                        ->reactive()
                        ->afterStateUpdated(function ($state, Forms\Set $set, Get $get) {
                            if (static::getCompanyMode((int) $get('perusahaan_id')) !== 'sap') return;

                            if (! $state) {
                                $set('expedition_supplier_name', null);
                                return;
                            }

                            $sap = app(SapHanaService::class);
                            $bp = $sap->getVendorByCode((string) $state);

                            $set('expedition_supplier_name', $bp['CardName'] ?? null);
                        })
                        ->visible(fn(Get $get) => static::getCompanyMode((int) $get('perusahaan_id')) === 'sap'),

                    // SSM mode: manual input nama ekspedisi
                    Forms\Components\TextInput::make('expedition_supplier_name')
                        ->label('Nama Ekspedisi')
                        ->disabled(fn(Get $get) => static::getCompanyMode((int) $get('perusahaan_id')) === 'sap')
                        ->dehydrated()
                        ->nullable(),
                ]),

            // =========================
            // 5) Informasi Pengiriman (manual, nullable)
            // =========================
            Forms\Components\Section::make('Informasi Pengiriman')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('no_resi')
                        ->label('No Resi')
                        ->maxLength(100)
                        ->nullable(),

                    Forms\Components\DatePicker::make('jangka_waktu_pelaksanaan')
                        ->label('Tanggal Pelaksanaan')
                        ->native(false)
                        ->displayFormat('d M Y')
                        ->nullable(),

                    Forms\Components\FileUpload::make('attachments')
                        ->label('Foto / File Lampiran')
                        ->multiple()
                        ->nullable()
                        ->preserveFilenames()
                        ->columnSpanFull(),
                ]),

            // =========================
            // 7) Notes
            // =========================
            Forms\Components\Section::make('Catatan')
                ->schema([
                    Forms\Components\Textarea::make('notes')
                        ->label('Notes')
                        ->rows(3)
                        ->columnSpanFull(),
                ]),

            // =========================
            // 8) Status
            // =========================
            Forms\Components\Section::make('Status')
                ->columns(1)
                ->schema([
                    Forms\Components\Select::make('status')
                        ->label('Status Pickup')
                        ->required()
                        ->options([
                            'scheduled' => 'Scheduled',
                            'shipped'   => 'Shipped',
                            'completed' => 'Completed',
                            'canceled'  => 'Canceled',
                        ])
                        ->default('scheduled')
                        ->helperText('Pilih status pickup saat ini. Gunakan tombol "Konfirmasi Pengiriman" jika sudah ada nomor resi.')
                        ->native(false),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->poll('5s')
            ->columns([
                Tables\Columns\TextColumn::make('perusahaan.nama_perusahaan')
                    ->label('Perusahaan')
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'CV Solusi Arya Prima' => 'danger',
                        'PT Sinergi Subur Makmur' => 'info',
                        default => 'primary',
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

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

                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Dibuat Oleh')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updater.name')
                    ->label('Diubah Oleh')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('po_number')
                    ->label('PO Number')
                    ->searchable(['po_number', 'package_id'])
                    ->copyable()
                    ->description(fn($record) => 'ID Paket: ' . $record->package_id)
                    ->wrap()
                    ->lineClamp(2),

                Tables\Columns\TextColumn::make('items_sum_pickup_quantity')
                    ->label('Qty')
                    ->sum('items', 'pickup_quantity')
                    ->numeric(0),

                Tables\Columns\TextColumn::make('vendor_name')
                    ->label('Vendor')
                    ->copyable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('vendor_address')
                    ->label('Alamat Vendor')
                    ->copyable()
                    ->toggleable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('kota')
                    ->label('Kota')
                    ->copyable()
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('vendor_pic_name')
                    ->label('PIC Vendor')
                    ->searchable()
                    ->copyable()
                    ->description(fn($record) => 'HP: ' . $record->vendor_pic_phone),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tgl Dibuat')
                    ->date('d M Y')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('pickup_date')
                    ->label('Pickup Date')
                    ->date('d M Y')
                    ->description(fn($record) => 'Hari: ' . $record->pickup_day),

                Tables\Columns\TextColumn::make('jangka_waktu_pelaksanaan')
                    ->label('Tgl Pelaksanaan')
                    ->date('d M Y')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('pickup_duration')
                    ->label('Durasi')
                    ->suffix(' Hari'),

                Tables\Columns\TextColumn::make('expedition_supplier_name')
                    ->label('Ekspedisi')
                    ->searchable()
                    ->copyable()
                    ->state(fn($record) => filled($record->expedition_supplier_name) ? $record->expedition_supplier_name : '-')
                    ->description(fn($record) => filled($record->no_resi) ? 'No Resi: ' . $record->no_resi : null)
                    ->wrap(),

                Tables\Columns\TextColumn::make('items_preview')
                    ->label('Barang')
                    ->getStateUsing(fn($record) => $record->items->take(3)->pluck('description')->implode(', '))
                    ->wrap()
                    ->copyable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('notes')
                    ->label('Catatan')
                    ->searchable()
                    ->copyable()
                    ->wrap()
                    ->lineClamp(2)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\BadgeColumn::make('tagihan_ke')
                    ->label('Tagihan Ke')
                    ->colors([
                        'danger' => 'SAP',
                        'info'    => 'SSM',
                        'warning' => 'INTERNAL',
                        'success'  => 'VENDOR',
                    ])
                    ->formatStateUsing(fn($state) => strtoupper($state ?? '-'))
                    ->toggleable(isToggledHiddenByDefault: true),


                Tables\Columns\TextColumn::make('pengambilan_cabang')
                    ->label('Cabang Ambil')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('tujuan_pengiriman')
                    ->label('Tujuan')
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('alamat_dropship')
                    ->label('Alamat Dropship')
                    ->copyable()
                    ->wrap()
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('alamat_ambil')
                    ->label('Alamat Ambil')
                    ->copyable()
                    ->wrap()
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
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
                TrashedFilter::make()
                    ->visible(fn() => auth()->user()?->hasRole('superadmin')),
            ])
            ->actions([
                ActionsActionGroup::make([
                    Tables\Actions\EditAction::make()->visible(function (Pickup $record) {
                        if (auth()->user()?->hasRole('superadmin')) {
                            return true;
                        }
                        return !in_array($record->status, ['completed', 'canceled']);
                    })
                        ->disabled(function (Pickup $record) {
                            if (auth()->user()?->hasRole('superadmin')) {
                                return false;
                            }
                            return in_array($record->status, ['completed', 'canceled']);
                        }),

                    Tables\Actions\Action::make('mark_shipped')
                        ->label('Proses Kirim')
                        ->icon('heroicon-o-truck')
                        ->color('info')
                        ->requiresConfirmation()
                        ->visible(fn(Pickup $record) => $record->status === 'scheduled')
                        ->action(fn(Pickup $record) => $record->update(['status' => 'shipped'])),

                    Tables\Actions\Action::make('complete_pickup')
                        ->label('Selesaikan Pickup')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(fn(Pickup $record) => $record->status === 'shipped' && !empty($record->no_resi))
                        ->action(fn(Pickup $record) => $record->update(['status' => 'completed'])),

                    Tables\Actions\Action::make('cancel')
                        ->label('Cancel')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->visible(fn(Pickup $record) => in_array($record->status, ['scheduled', 'shipped']))
                        ->action(fn(Pickup $record) => $record->update(['status' => 'canceled'])),
                    Tables\Actions\DeleteAction::make()
                        ->visible(function (Pickup $record) {
                            $user = auth()->user();

                            // Superadmin bisa delete semua
                            if ($user?->hasRole('superadmin')) {
                                return true;
                            }

                            // User biasa hanya bisa delete yang dia buat
                            return $record->created_by === $user?->id;
                        }),
                    Tables\Actions\RestoreAction::make(),
                    Tables\Actions\ForceDeleteAction::make()
                        ->visible(fn() => auth()->user()?->hasRole('superadmin')),

                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make()
                        ->visible(fn() => auth()->user()?->hasRole('superadmin')),

                ]),
            ])->actionsPosition(\Filament\Tables\Enums\ActionsPosition::BeforeColumns);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListPickups::route('/'),
            'create' => Pages\CreatePickup::route('/create'),
            'edit'   => Pages\EditPickup::route('/{record}/edit'),
        ];
    }

    protected static function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = Auth::id();
        return $data;
    }

    protected static function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] = Auth::id();
        return $data;
    }
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                \Illuminate\Database\Eloquent\SoftDeletingScope::class,
            ]);
    }
}
