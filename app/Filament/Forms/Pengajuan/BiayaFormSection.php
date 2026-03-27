<?php

namespace App\Filament\Forms\Pengajuan;

use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Illuminate\Support\Collection;
use App\Models\Service;
use App\Models\ServiceItem;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Toggle;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class BiayaFormSection
{
    public static function schema(): array
    {
        return [
            Section::make('Pengajuan RAB Biaya Service')
                ->schema([

                    Repeater::make('pengajuan_biaya_services')
                        ->label('Form RAB Biaya Service')
                        ->relationship('pengajuan_biaya_services')
                        ->schema([

                            // Relasi service & item
                            Select::make('service_id')
                                ->label('Service')
                                ->searchable()
                                ->preload(false) // jangan load semua data
                                // === pencarian server-side, difilter per role ===
                                ->getSearchResultsUsing(function (string $search) {
                                    $user = Auth::user();

                                    $q = Service::query()
                                        ->when(! $user->hasRole('superadmin'), fn($qq) => $qq->where('user_id', $user->id))
                                        ->when($search !== '', fn($qq) => $qq->where('nomer_so', 'like', "%{$search}%"))
                                        ->orderByDesc('id')
                                        ->limit(50);

                                    return $q->pluck('nomer_so', 'id')->toArray(); // [id => label]
                                })
                                // label saat nilai sudah terpilih
                                ->getOptionLabelUsing(fn($value) => Service::find($value)?->nomer_so)
                                ->required()
                                ->live()
                                // guard server-side
                                ->rules(function () {
                                    $user = Auth::user();

                                    if ($user->hasRole('superadmin')) {
                                        return ['required', Rule::exists('services', 'id')];
                                    }

                                    return [
                                        'required',
                                        Rule::exists('services', 'id')->where(fn($q) => $q->where('user_id', $user->id)),
                                    ];
                                })
                                ->afterStateUpdated(function ($state, Set $set) {
                                    // reset item ketika service berubah
                                    $set('service_item_id', null);
                                }),

                            Select::make('service_item_id')
                                ->label('Nama Barang')
                                ->options(function (Get $get) {
                                    $serviceId = $get('service_id');

                                    if (!$serviceId) {
                                        return [];
                                    }

                                    return ServiceItem::where('service_id', $serviceId)
                                        ->pluck('nama_barang', 'id');
                                })
                                ->searchable()
                                ->nullable()
                                ->disabled(fn(Get $get) => !$get('service_id')),

                            Textarea::make('deskripsi')
                                ->label('Deskripsi Biaya')
                                ->placeholder('Tuliskan detail biaya di sini...')
                                ->rows(2)
                                ->maxLength(65535)
                                ->columnSpanFull(),

                            // Jumlah
                            TextInput::make('jumlah')
                                ->label('Jumlah')
                                ->numeric()
                                ->default(1)
                                ->minValue(1)
                                ->required()
                                ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                    // Hitung subtotal langsung
                                    $harga = (int) str_replace('.', '', $get('harga_satuan') ?? 0);
                                    $subtotal = (int) $state * $harga;
                                    $set('subtotal', $subtotal);

                                    // Update total biaya
                                    self::updateParentTotalBiaya($get, $set);
                                }),

                            // Harga Satuan
                            TextInput::make('harga_satuan')
                                ->label('Harga Satuan')
                                ->prefix('Rp ')
                                ->placeholder('Contoh: 250000')
                                ->required()
                                ->default(0)
                                ->extraAttributes(['class' => 'currency-input'])
                                ->afterStateHydrated(
                                    fn(TextInput $c, $state) =>
                                    $c->state($state ? number_format((int) $state, 0, ',', '.') : null)
                                )
                                ->dehydrateStateUsing(fn($state) => $state ? (int) str_replace('.', '', $state) : 0)
                                ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                    $harga = (int) str_replace('.', '', $state ?? 0);
                                    $set('harga_satuan', $harga);

                                    // Hitung subtotal langsung
                                    $jumlah = (int) $get('jumlah') ?? 1;
                                    $subtotal = $jumlah * $harga;
                                    $set('subtotal', $subtotal);

                                    // Update total biaya
                                    self::updateParentTotalBiaya($get, $set);
                                }),

                            // Subtotal
                            TextInput::make('subtotal')
                                ->label('Subtotal')
                                ->prefix('Rp ')
                                ->disabled()
                                ->dehydrated()
                                ->default(0)
                                ->extraAttributes(['class' => 'currency-input'])
                                ->afterStateHydrated(
                                    fn(TextInput $c, $state) =>
                                    $c->state($state ? number_format((int) $state, 0, ',', '.') : null)
                                )
                                ->dehydrateStateUsing(fn($state) => $state ? (int) str_replace('.', '', $state) : 0),
                        ])
                        ->columns(2)
                        ->addActionLabel('Tambah Biaya Service')
                        ->itemLabel('Detail Biaya Service')
                        ->defaultItems(1)
                        ->columnSpanFull()
                        ->afterStateUpdated(function (Get $get, Set $set) {
                            self::updateParentTotalBiaya($get, $set);
                        })
                        ->deleteAction(function (Get $get, Set $set) {
                            self::updateParentTotalBiaya($get, $set);
                        }),

                    // Total semua biaya
                    TextInput::make('total_biaya')
                        ->label('Total Semua Biaya Service')
                        ->disabled()
                        ->dehydrated()
                        ->default(0)
                        ->prefix('Rp ')
                        ->extraAttributes(['class' => 'currency-input', 'id' => 'total_biaya_field'])
                        ->afterStateHydrated(
                            fn(TextInput $c, $state) =>
                            $c->state($state ? number_format((int) $state, 0, ',', '.') : null)
                        )
                        ->dehydrateStateUsing(fn($state) => $state ? (int) str_replace('.', '', $state) : 0)
                        ->afterStateUpdated(function ($state, Set $set) {
                            // Simpan nilai numerik untuk database
                            $set('total_biaya', $state ? (int) str_replace('.', '', $state) : 0);
                        }),
                    Toggle::make('lampiran_biaya_service')
                        ->label('Tambahkan Lampiran Biaya Service')
                        ->default(false)
                        ->onIcon('heroicon-s-check')
                        ->offIcon('heroicon-s-x-mark')
                        ->onColor('success')
                        ->offColor('danger')
                        ->reactive()
                        ->dehydrated(),
                    Repeater::make('lampiranBiayaServices')
                        ->label('Lampiran RAB Biaya Service')
                        ->relationship('lampiranBiayaServices')
                        ->schema([
                            FileUpload::make('file_path')
                                ->label('File Lampiran (PDF/Gambar)')
                                ->disk('public')
                                ->directory('lampiran-biaya')
                                ->preserveFilenames()
                                ->acceptedFileTypes(['application/pdf', 'image/*'])
                                ->maxSize(10240)
                                ->required()
                                ->reactive()
                                ->afterStateUpdated(function (Set $set, $state) {
                                    if (is_array($state) && count($state) > 0) {
                                        $file = array_key_first($state);
                                        if ($file) {
                                            $filename = pathinfo($file, PATHINFO_BASENAME);
                                            $set('original_name', $filename);
                                        }
                                    }
                                }),

                            TextInput::make('original_name')
                                ->label('Nama Lampiran')
                                ->required()
                                ->maxLength(255),
                        ])
                        ->defaultItems(1)
                        ->visible(fn($get) => $get('lampiran_biaya_service') === true),
                ])
                ->visible(fn(Get $get) => $get('tipe_rab_id') == 6),
        ];
    }

    /**
     * Update total biaya di parent form
     */
    private static function updateParentTotalBiaya(Get $get, Set $set): void
    {
        $items = $get('pengajuan_biaya_services') ?? [];

        $grandTotal = 0;
        foreach ($items as $item) {
            $subtotal = $item['subtotal'] ?? 0;
            // Pastikan kita menggunakan nilai numerik, bukan yang sudah diformat
            if (is_string($subtotal) && str_contains($subtotal, '.')) {
                $subtotal = (int) str_replace('.', '', $subtotal);
            }
            $grandTotal += (int) $subtotal;
        }

        // Update field total_biaya di parent form (jumlah dari semua subtotal)
        $set('total_biaya', $grandTotal);
    }

    /**
     * Mendapatkan semua service_id yang sudah dipilih di repeater
     */
    private static function getAllSelectedServiceIds(Get $get): array
    {
        $items = $get('pengajuan_biaya_services') ?? [];
        $selectedServiceIds = [];

        foreach ($items as $item) {
            if (!empty($item['service_id'])) {
                $selectedServiceIds[] = (int) $item['service_id'];
            }
        }

        return array_unique($selectedServiceIds);
    }
}
