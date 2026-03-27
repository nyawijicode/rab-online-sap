<?php

namespace App\Filament\Resources;

use App\Enums\StagingEnum;
use App\Filament\Resources\ServiceResource\Pages;
use App\Filament\Resources\ServiceResource\RelationManagers\AllLogsRelationManager;
use App\Models\CustomerMonitor;
use App\Models\ProjectMonitor;
use App\Models\Service;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Filament\Forms\Get;
use Filament\Forms\Components\Select as FSelect;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;

class ServiceResource extends Resource
{
    protected static ?string $model = Service::class;
    protected static ?string $navigationLabel = 'Request Service';
    protected static ?string $label           = 'Request Service';
    protected static ?string $navigationGroup = 'Request Sales'; // SAMA dgn ServiceResource
    protected static ?string $slug            = 'request-service';
    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informasi Service')
                    ->schema([
                        // ================= Jenis Servis =================
                        FSelect::make('jenis_servis')
                            ->label('Jenis Servis')
                            ->options([
                                'inventaris' => 'Inventaris',
                                'paket'      => 'Paket/Project',
                                'ssm'        => 'SSM',
                            ])
                            ->default('paket')
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                // Set company otomatis
                                if (in_array($state, ['paket', 'inventaris'])) {
                                    $set('company', 'sap');
                                } elseif ($state === 'ssm') {
                                    $set('company', 'ssm');
                                }

                                if ($state === 'inventaris') {
                                    $set('id_paket', '-');
                                    $set('nama_dinas', '-');
                                    $set('kontak', '-');
                                    $set('no_telepon', '-');
                                } elseif ($state === 'ssm') {
                                    // Untuk SSM manual input
                                    $set('id_paket', null);
                                    $set('nama_dinas', null);
                                } else {
                                    // kembali ke paket
                                    $set('id_paket', null);
                                    $set('nama_dinas', null);
                                    $set('kontak', null);
                                    $set('no_telepon', null);
                                }
                            }),
                        Hidden::make('company')
                            ->default('sap')
                            ->dehydrated(true),
                        // ============= Informasi Paket / SSM =============
                        Section::make('Informasi Paket')
                            ->visible(fn(Get $get) => in_array($get('jenis_servis'), ['paket', 'ssm']))
                            ->schema([

                                // ================== ID Paket ==================
                                // Jika paket → FSelect
                                // Jika ssm → TextInput manual
                                Forms\Components\Group::make([
                                    FSelect::make('id_paket')
                                        ->label('ID Paket')
                                        ->visible(fn(Get $get) => $get('jenis_servis') === 'paket')
                                        ->searchable()
                                        ->reactive()
                                        ->getSearchResultsUsing(function (string $search) {
                                            return \App\Models\ProjectMonitor::query()
                                                ->where(fn($q) => $q->where('name', 'ILIKE', "%{$search}%")
                                                    ->orWhere('code', 'ILIKE', "%{$search}%"))
                                                ->limit(50)
                                                ->get()
                                                ->mapWithKeys(fn($p) => [$p->code => "{$p->name}"])
                                                ->toArray();
                                        })
                                        ->getOptionLabelUsing(function ($value) {
                                            if (!$value || $value === '-') return $value;
                                            $p = \App\Models\ProjectMonitor::query()->where('code', $value)->first();
                                            return $p ? "{$p->name}" : $value;
                                        })
                                        ->afterStateUpdated(function ($state, callable $set, Get $get) {
                                            if ($get('jenis_servis') !== 'paket') return;

                                            if (!$state || $state === '-') {
                                                $set('nama_dinas', null);
                                                return;
                                            }

                                            $project = \App\Models\ProjectMonitor::query()
                                                ->with('customer:id,name')
                                                ->where('code', $state)
                                                ->first();

                                            $set('nama_dinas', $project?->customer?->name ?: '-');
                                        })
                                        ->required(fn(Get $get) => $get('jenis_servis') === 'paket')
                                        ->dehydrateStateUsing(
                                            fn($state, Get $get) =>
                                            $get('jenis_servis') === 'inventaris'
                                                ? '-'
                                                : (string) ($state ?: '-')
                                        ),

                                    TextInput::make('id_paket')
                                        ->label('ID Paket (SSM)')
                                        ->visible(fn(Get $get) => $get('jenis_servis') === 'ssm')
                                        ->required(fn(Get $get) => $get('jenis_servis') === 'ssm')
                                        ->maxLength(255),
                                ]),

                                // ================= Nama Dinas =================
                                Forms\Components\Group::make([
                                    TextInput::make('nama_dinas')
                                        ->label('Nama Dinas')
                                        ->visible(fn(Get $get) => $get('jenis_servis') === 'paket')
                                        ->disabled()
                                        ->dehydrated(true)   // <--- WAJIB
                                        ->required(fn(Get $get) => $get('jenis_servis') === 'paket')
                                        ->dehydrateStateUsing(
                                            fn($state, Get $get) =>
                                            $get('jenis_servis') === 'inventaris'
                                                ? '-'
                                                : (string) ($state ?: '-')
                                        ),

                                    TextInput::make('nama_dinas')
                                        ->label('Nama Dinas (SSM)')
                                        ->visible(fn(Get $get) => $get('jenis_servis') === 'ssm')
                                        ->required(fn(Get $get) => $get('jenis_servis') === 'ssm')
                                        ->maxLength(255),
                                ]),

                                // ================= Kontak =================
                                Section::make('Kontak Informasi')
                                    ->schema([
                                        TextInput::make('kontak')
                                            ->label('Nama Kontak')
                                            ->required(fn(Get $get) => in_array($get('jenis_servis'), ['paket', 'ssm']))
                                            ->dehydrateStateUsing(
                                                fn($state, Get $get) =>
                                                $get('jenis_servis') === 'inventaris'
                                                    ? '-'
                                                    : (string) ($state ?: '-')
                                            ),

                                        TextInput::make('no_telepon')
                                            ->label('No. Telepon')
                                            ->tel()
                                            ->required(fn(Get $get) => in_array($get('jenis_servis'), ['paket', 'ssm']))
                                            ->dehydrateStateUsing(
                                                fn($state, Get $get) =>
                                                $get('jenis_servis') === 'inventaris'
                                                    ? '-'
                                                    : (string) ($state ?: '-')
                                            ),
                                    ])->columns(2),
                            ])->columns(2),

                        Forms\Components\Section::make('Informasi SO & Staging')
                            ->schema([
                                Forms\Components\TextInput::make('nomer_so')
                                    ->label('No. Service Order')
                                    ->default(fn() => \App\Models\Service::peekNextNomorSO())
                                    ->disabled() // biar user ga bisa ubah manual
                                    ->dehydrated() // tetap disimpan ke DB
                                    ->required(),

                                Select::make('staging')
                                    ->label('Status Staging')
                                    // Opsi yang ditampilkan = opsi yang diizinkan + (selipkan current value agar label tetap rapi)
                                    ->options(function (callable $get) {
                                        $opts = self::allowedStagingOptionsForCurrentUser(); // [value => label]

                                        // Ambil state saat ini dari record
                                        $state = $get('staging');
                                        $current = $state instanceof StagingEnum ? $state->value : (string) $state;

                                        // Jika current value tidak ada di opsi (misal user biasa), selipkan agar label bisa dirender
                                        if ($current !== '' && ! array_key_exists($current, $opts)) {
                                            if ($enum = StagingEnum::tryFrom($current)) {
                                                // taruh di paling depan supaya langsung kelihatan
                                                $opts = [$current => $enum->label()] + $opts;
                                            } else {
                                                // fallback kalau ada value aneh
                                                $opts = [$current => Str::headline($current)] + $opts;
                                            }
                                        }

                                        return $opts;
                                    })
                                    // Placeholder pakai label yang rapi kalau belum ada pilihan
                                    ->placeholder(function (callable $get) {
                                        $state = $get('staging');
                                        $enum  = $state instanceof StagingEnum ? $state : StagingEnum::tryFrom((string) $state);

                                        if ($enum) {
                                            return $enum->label();
                                        }

                                        $opts = self::allowedStagingOptionsForCurrentUser();
                                        return empty($opts)
                                            ? 'Tidak ada opsi'
                                            : (count($opts) === 1 ? reset($opts) : 'Pilih Status Staging');
                                    })
                                    // Pastikan state yang dipakai select adalah string value, bukan object enum
                                    ->afterStateHydrated(
                                        fn(Select $component, $state) =>
                                        $component->state($state instanceof StagingEnum ? $state->value : (string) $state)
                                    )
                                    // Saat menyimpan tetap kirim value string
                                    ->dehydrateStateUsing(
                                        fn($state) =>
                                        $state instanceof StagingEnum ? $state->value : (string) $state
                                    )
                                    ->required()
                                    ->default(StagingEnum::REQUEST->value)
                                    ->native(false)
                                    // Disable kalau tidak ada/tinggal satu opsi
                                    ->disabled(
                                        fn() =>
                                        empty(self::allowedStagingOptionsForCurrentUser())
                                            || count(self::allowedStagingOptionsForCurrentUser()) <= 1
                                    ),
                                Forms\Components\Textarea::make('keterangan_staging')
                                    ->label('Keterangan Staging')
                                    ->default('-')
                                    ->columnSpanFull(),
                            ])
                            ->columns(2),
                    ])
                    ->columns(2),

                Repeater::make('items')
                    ->label(false)
                    ->relationship()
                    ->schema([
                        Forms\Components\Section::make('Detail Barang')
                            ->schema([
                                Forms\Components\Textarea::make('kerusakan')
                                    ->label('Deskripsi Kerusakan')
                                    ->required()
                                    ->columnSpanFull(),
                                Forms\Components\TextInput::make('nama_barang')
                                    ->label('Nama Barang')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('noserial')
                                    ->label('No. Serial')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\Select::make('masih_garansi')
                                    ->label('Masih Garansi')
                                    ->options([
                                        'Y' => 'Ya',
                                        'T' => 'Tidak',
                                    ])
                                    ->default('T')
                                    ->required()
                                    ->native(false),
                            ])
                            ->columns(2),
                    ])
                    ->columnSpanFull()
                    ->defaultItems(1)
                    ->minItems(1)
                    ->itemLabel('Barang yang di Service')
                    ->addActionLabel('Tambah Barang'),

                Forms\Components\Section::make('Lampiran Foto Service')
                    ->description('Upload foto pendukung (kerusakan, dokumen, kondisi sebelum/sesudah).')
                    ->schema([
                        Repeater::make('photos')
                            ->label(false)
                            ->relationship('photos')
                            ->schema([
                                Forms\Components\FileUpload::make('image_path')
                                    ->label('Foto')
                                    ->image()
                                    ->disk('public')
                                    ->directory('service/lampiran')
                                    ->visibility('public')
                                    ->imageEditor()
                                    ->openable()
                                    ->downloadable()
                                    ->required(),

                                Forms\Components\Textarea::make('keterangan')
                                    ->label('Keterangan')
                                    ->rows(3)
                                    ->nullable()
                                    ->columnSpanFull(),

                                Forms\Components\Hidden::make('uploaded_by')
                                    ->default(fn() => auth()->id())
                                    ->dehydrated(true),
                            ])
                            ->defaultItems(0)
                            ->addActionLabel('Tambah Foto')
                            ->reorderable(true)
                            ->collapsible()
                            ->itemLabel(fn(array $state) => !empty($state['keterangan'])
                                ? \Illuminate\Support\Str::limit($state['keterangan'], 35)
                                : 'Foto'),
                    ])
                    ->columnSpanFull(),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn($query) => $query->with('items')) // <--- penting
            ->poll('5s')
            ->columns([
                Tables\Columns\TextColumn::make('user.name')->label('Pemohon')->searchable(),
                Tables\Columns\TextColumn::make('jenis_servis')
                    ->label('Jenis')
                    ->badge()
                    ->formatStateUsing(function ($state, Service $record) {

                        $jenis = match ($state) {
                            'inventaris' => 'Inventaris',
                            'paket'      => 'Paket',
                            'ssm'        => 'Paket',
                            default      => ucfirst($state),
                        };

                        $companyText = match ($record->company) {
                            'sap' => 'CV Solusi Arya Prima',
                            'ssm' => 'PT Sinergi Subur Makmur',
                            default => '-',
                        };

                        return "
            <div class='text-center leading-tight'>
                {$jenis}
                <br>
                <span class='text-xs opacity-75'>{$companyText}</span>
            </div>
        ";
                    })
                    ->html()
                    ->color(function ($state, Service $record) {
                        return match ($record->company) {
                            'sap' => 'success', // hijau
                            'ssm' => 'info',    // biru
                            default => 'gray',
                        };
                    })
                    ->toggleable(true),

                Tables\Columns\TextColumn::make('nomer_so')
                    ->label('No Servis')
                    ->searchable()
                    ->limit(30)
                    ->toggleable(true),

                Tables\Columns\TextColumn::make('id_paket')
                    ->label('ID Paket')
                    ->searchable()
                    ->toggleable(true),

                Tables\Columns\TextColumn::make('nama_dinas')
                    ->label('Dinas/Kontak')
                    ->searchable()
                    ->description(function (Service $record) {
                        $kontak = $record->kontak ?? '-';
                        $telepon = $record->no_telepon ?? '-';
                        return "{$kontak}\n{$telepon}";
                    })
                    ->tooltip(function (Service $record) {
                        $dinas = $record->nama_dinas ?? '-';
                        $kontak = $record->kontak ?? '-';
                        $telepon = $record->no_telepon ?? '-';
                        // tooltip pakai \n, tapi ini nanti tergantung support framework
                        return "Nama Dinas: {$dinas}\nKontak: {$kontak}\nTelepon: {$telepon}";
                    })
                    ->limit(30)
                    ->copyable(function (Service $record) {
                        $dinas = $record->nama_dinas ?? '-';
                        $kontak = $record->kontak ?? '-';
                        $telepon = $record->no_telepon ?? '-';
                        return "Nama Dinas: {$dinas}\nKontak: {$kontak}\nTelepon: {$telepon}";
                    })
                    ->copyMessage('Disalin ke clipboard!')
                    ->toggleable(true),

                // Tampilkan staging dengan badge warna
                Tables\Columns\TextColumn::make('staging_value')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(function ($state): string {
                        $enum = $state instanceof StagingEnum ? $state : StagingEnum::tryFrom((string) $state);
                        return $enum?->label() ?? '-';
                    })
                    ->color(function ($state): string {
                        $val = $state instanceof StagingEnum ? $state->value : (string) $state;
                        return match ($val) {
                            StagingEnum::REQUEST->value        => 'gray',
                            StagingEnum::CEK_KERUSAKAN->value  => 'info',
                            StagingEnum::ADA_BIAYA->value      => 'warning',
                            StagingEnum::CLOSE->value          => 'danger',
                            StagingEnum::APPROVE->value        => 'success',
                            default                            => 'secondary',
                        };
                    })
                    ->searchable(),

                // Tables\Columns\TextColumn::make('kontak')
                //     ->label('Kontak')
                //     ->searchable()
                //     ->limit(20)
                //     ->toggleable(true),

                // Tables\Columns\TextColumn::make('no_telepon')
                //     ->label('Telepon')
                //     ->searchable()
                //     ->toggleable(true),

                // Nama barang
                Tables\Columns\TextColumn::make('items.nama_barang')
                    ->label('Nama Barang')
                    ->formatStateUsing(
                        fn($state, $record) =>
                        $record->items->pluck('nama_barang')->filter()->join('<br>')
                    )
                    ->html(),

                // No serial
                Tables\Columns\TextColumn::make('items.noserial')
                    ->label('No. Serial')
                    ->formatStateUsing(
                        fn($state, $record) =>
                        $record->items->pluck('noserial')->filter()->join('<br>')
                    )
                    ->html(),

                // Garansi
                Tables\Columns\TextColumn::make('items.masih_garansi')
                    ->label('Garansi')
                    ->formatStateUsing(
                        fn($state, $record) =>
                        $record->items
                            ->map(fn($i) => $i->masih_garansi === 'Y' ? 'Ya' : 'Tidak')
                            ->join('<br>')
                    )
                    ->html(),

                Tables\Columns\TextColumn::make('keterangan_staging')
                    ->label('Keterangan')
                    ->wrap() // ini built-in Filament v3, ganti truncate jadi wrap
                    ->searchable()
                    ->extraAttributes([
                        'class' => 'whitespace-normal break-words text-left max-w-xs mx-auto',
                        'style' => 'min-width: 200px; max-width: 300px;',
                    ])
                    ->toggleable(true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->toggleable(true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Diupdate')
                    ->dateTime('d M Y H:i')
                    ->toggleable(true),
                Tables\Columns\TextColumn::make('approved_info')
                    ->label('Konfirmasi Selesai')
                    ->html()
                    ->getStateUsing(function ($record) {
                        $approvedStatuses = $record->statuses()
                            ->whereNotNull('is_approved')
                            ->with('user')
                            ->orderBy('approved_at')
                            ->get();

                        if ($approvedStatuses->isEmpty()) {
                            return '-';
                        }

                        $list = $approvedStatuses->map(function ($status) {
                            $name = e($status->user?->name ?? '-');
                            $approvedText = $status->is_approved ? 'Disetujui' : 'Ditolak';
                            $date = $status->approved_at
                                ? \Carbon\Carbon::parse($status->approved_at)->translatedFormat('d F Y H:i')
                                : '-';
                            $keterangan = $status->is_approved
                                ? $status->catatan_approve
                                : $status->alasan_ditolak;
                            return "<div>{$date} ({$approvedText})<br><span style=\"font-size:13px;color:#aaa;\">{$keterangan}</span></div>";
                        })->implode('');

                        return $list;
                    })
                    ->toggleable(true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('staging')
                    ->label('Status Staging')
                    ->options(StagingEnum::options()),

                Tables\Filters\SelectFilter::make('masih_garansi')
                    ->label('Status Garansi')
                    ->options([
                        'Y' => 'Masih Garansi',
                        'T' => 'Tidak Garansi',
                    ]),
                // Filter untuk menampilkan data yang dihapus
                Tables\Filters\TrashedFilter::make()
                    ->label('Status Data')
                    ->placeholder('Data aktif')
                    ->options([
                        'withoutTrashed' => 'Data aktif',
                        'onlyTrashed' => 'Data dihapus',
                        'all' => 'Semua data',
                    ])
                    ->visible(fn(): bool => auth()->user()->hasRole('superadmin')),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('staging')
                    ->label('Status Staging')
                    ->options(StagingEnum::options()),
                Filter::make('created_at_range')
                    ->label('Tanggal Dibuat')
                    ->form([
                        Fieldset::make('Tanggal Dibuat') // judul grup di panel filter
                            ->schema([
                                DatePicker::make('dari')
                                    ->label('Dari')
                                    ->native(false)
                                    ->displayFormat('d/m/Y'),
                                DatePicker::make('sampai')
                                    ->label('Sampai')
                                    ->native(false)
                                    ->displayFormat('d/m/Y'),
                            ])
                            ->columns(2), // tampil berdampingan
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $from = $data['dari']   ?? null;
                        $to   = $data['sampai'] ?? null;

                        return $query
                            ->when($from && $to, fn($q) => $q->whereBetween(
                                'created_at',
                                [Carbon::parse($from)->startOfDay(), Carbon::parse($to)->endOfDay()]
                            ))
                            ->when($from && ! $to, fn($q) => $q->whereDate('created_at', '>=', $from))
                            ->when($to   && ! $from, fn($q) => $q->whereDate('created_at', '<=', $to));
                    })
                    ->indicateUsing(function (array $data): array {
                        $chips = [];
                        if (!empty($data['dari']))   $chips[] = 'Mulai '  . Carbon::parse($data['dari'])->translatedFormat('d M Y');
                        if (!empty($data['sampai'])) $chips[] = 'Sampai ' . Carbon::parse($data['sampai'])->translatedFormat('d M Y');
                        return $chips;
                    }),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->filtersFormColumns(2) // atur jumlah kolom di atas
            ->filtersFormWidth(MaxWidth::FourExtraLarge) // kalau perlu lebih lebar
            ->filtersFormMaxHeight('400px') // bisa ditambah scroll
            ->actions([
                Tables\Actions\ActionGroup::make([
                    // SETUJUI (oleh pemohon)
                    Tables\Actions\Action::make('Setujui')
                        ->label('Setujui')
                        ->color('success')
                        ->icon('heroicon-o-check-circle')
                        ->requiresConfirmation()
                        ->form([
                            Textarea::make('catatan_approve')
                                ->label('Catatan (opsional)')
                                ->rows(2),
                        ])
                        ->action(function (Service $record, array $data) {
                            $user = auth()->user();

                            // pemohon doang
                            if ((int) $record->user_id !== (int) $user->id) {
                                Notification::make()
                                    ->title('Hanya pemohon yang bisa menyetujui request ini.')
                                    ->danger()
                                    ->send();
                                return;
                            }

                            // jangan dobel vote
                            if ($record->statuses()->where('user_id', $user->id)->exists()) {
                                Notification::make()
                                    ->title('Anda sudah memberikan keputusan untuk service ini.')
                                    ->warning()
                                    ->send();
                                return;
                            }

                            $record->statuses()->create([
                                'user_id' => $user->id,
                                'is_approved' => true,
                                'approved_at' => now(),
                                'catatan_approve' => $data['catatan_approve'] ?? null,
                            ]);

                            Notification::make()
                                ->title('Service request berhasil disetujui.')
                                ->success()
                                ->send();
                        })
                        ->visible(function (Service $record) {
                            $user = auth()->user();

                            return $user
                                && (int) $record->user_id === (int) $user->id
                                && (($record->staging instanceof \App\Enums\StagingEnum)
                                    ? $record->staging->value
                                    : (string) $record->staging) === \App\Enums\StagingEnum::CLOSE->value
                                && ! $record->statuses()->where('user_id', $user->id)->exists();
                        }),

                    // TOLAK (oleh pemohon)
                    Tables\Actions\Action::make('Tolak')
                        ->label('Tolak')
                        ->color('danger')
                        ->icon('heroicon-o-x-circle')
                        ->requiresConfirmation()
                        ->form([
                            Textarea::make('alasan_ditolak')
                                ->label('Alasan Penolakan')
                                ->required(),
                        ])
                        ->action(function (Service $record, array $data) {
                            $user = auth()->user();

                            if ((int) $record->user_id !== (int) $user->id) {
                                Notification::make()
                                    ->title('Hanya pemohon yang bisa menolak request ini.')
                                    ->danger()
                                    ->send();
                                return;
                            }

                            if ($record->statuses()->where('user_id', $user->id)->exists()) {
                                Notification::make()
                                    ->title('Anda sudah memberikan keputusan untuk service ini.')
                                    ->warning()
                                    ->send();
                                return;
                            }

                            $record->statuses()->create([
                                'user_id' => $user->id,
                                'is_approved' => false,
                                'approved_at' => now(),
                                'alasan_ditolak' => $data['alasan_ditolak'],
                            ]);

                            Notification::make()
                                ->title('Service request telah ditolak.')
                                ->danger()
                                ->send();
                        })
                        ->visible(function (Service $record) {
                            $user = auth()->user();

                            return $user
                                && (int) $record->user_id === (int) $user->id
                                && (($record->staging instanceof \App\Enums\StagingEnum)
                                    ? $record->staging->value
                                    : (string) $record->staging) === \App\Enums\StagingEnum::CLOSE->value
                                && ! $record->statuses()->where('user_id', $user->id)->exists();
                        }),

                    // Restore action untuk data yang dihapus
                    Tables\Actions\RestoreAction::make()
                        ->label('Pulihkan')
                        ->visible(
                            fn(Service $record): bool =>
                            $record->trashed() && Auth::user()->hasRole('superadmin')
                        )
                        ->tooltip('Pulihkan data yang dihapus'),

                    // Force delete action
                    Tables\Actions\ForceDeleteAction::make()
                        ->label('Hapus Permanen')
                        ->visible(
                            fn(Service $record): bool =>
                            $record->trashed() && Auth::user()->hasRole('superadmin')
                        )
                        ->tooltip('Hapus data secara permanen'),
                    // Action custom untuk ubah staging
                    Tables\Actions\Action::make('updateStaging')
                        ->label('Ubah Staging')
                        ->icon('heroicon-o-arrow-path')
                        ->modalHeading('Ubah Status Staging')
                        ->modalDescription('Pilih status staging baru untuk service ini')
                        ->form([
                            \Filament\Forms\Components\Select::make('staging')
                                ->label('Status Staging Baru')
                                ->options(fn() => self::allowedStagingOptionsForCurrentUser())
                                ->required()
                                ->native(false),
                            \Filament\Forms\Components\Textarea::make('keterangan')
                                ->label('Keterangan Perubahan')
                                ->placeholder('Tambahkan alasan atau keterangan perubahan status...')
                                ->required()
                        ])
                        ->action(function (Service $record, array $data): void {
                            // Simpan nilai lama sebelum diubah
                            $oldStaging = $record->staging->value;
                            $newStagingValue = $data['staging'];
                            $keterangan = $data['keterangan'];

                            // Dapatkan enum instance dari nilai string
                            $newStagingEnum = StagingEnum::tryFrom($newStagingValue);

                            if (!$newStagingEnum) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Error')
                                    ->body('Status staging tidak valid')
                                    ->danger()
                                    ->send();
                                return;
                            }

                            // Simpan perubahan ke service
                            $record->staging = $newStagingEnum;
                            $record->keterangan_staging = $keterangan;
                            $record->save();

                            // Simpan log perubahan menggunakan ServiceLogService baru
                            \App\Services\ServiceLogService::logStagingChange($record, $oldStaging, $newStagingValue, $keterangan);

                            // Hook untuk status ada_biaya
                            if ($newStagingValue === StagingEnum::ADA_BIAYA->value) {
                                // Kirim notifikasi ke sales
                                // Notification::send($salesUsers, new ServiceCostNotification($record));
                            }

                            \Filament\Notifications\Notification::make()
                                ->title('Status Staging Diperbarui')
                                ->body('Status staging berhasil diubah dari ' .
                                    StagingEnum::tryFrom($oldStaging)?->label() ?? $oldStaging .
                                    ' menjadi ' .
                                    $newStagingEnum->label())
                                ->success()
                                ->send();
                        })
                        ->visible(
                            fn(Service $record): bool =>
                            auth()->check() && !empty(self::allowedStagingOptionsForCurrentUser())
                        )
                        ->color('warning')
                        ->tooltip('Ubah Status Staging'),

                    // Action untuk melihat log perubahan
                    Tables\Actions\Action::make('viewServiceLogs')
                        ->label('Lihat Log')
                        ->icon('heroicon-o-document-text')
                        ->modalHeading('Log Perubahan Service')
                        ->modalDescription('History semua perubahan untuk service ini')
                        ->modalContent(function (Service $record) {
                            $logs = $record->serviceLogs()
                                ->orderBy('created_at', 'desc')
                                ->paginate(10); // Gunakan pagination

                            return view('filament.resources.service-resource.service-logs', [
                                'logs' => $logs
                            ]);
                        })
                        ->slideOver()
                        ->modalWidth('screen')
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('Tutup')
                        ->color('info')
                        ->tooltip('Lihat History Perubahan'),

                    // Edit hanya untuk servis & superadmin (manager tidak bisa)
                    Tables\Actions\EditAction::make()
                        ->label('Edit')
                        ->visible(fn(Service $record) => self::canEditRecord($record)),

                    Tables\Actions\ViewAction::make()
                        ->label('Lihat')
                        ->modalWidth('screen')
                        ->slideOver()
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('Tutup')
                        ->closeModalByClickingAway(false),

                    // Hapus: user biasa & superadmin (servis/manager tidak)
                    Tables\Actions\DeleteAction::make()
                        ->label('Hapus')
                        ->visible(fn(Service $record) => self::canDeleteRecord($record)),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Bulk delete biarkan hanya superadmin (mengikuti canDeleteRecord di policy real,
                    // atau batasi di sini jika ingin)
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn() => Auth::user()?->hasRole('superadmin') === true),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->deferLoading()
            ->actionsPosition(\Filament\Tables\Enums\ActionsPosition::BeforeColumns);
    }

    public static function getRelations(): array
    {
        return [
            AllLogsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListServices::route('/'),
            'create' => Pages\CreateService::route('/create'),
            'edit'   => Pages\EditService::route('/{record}/edit'),
        ];
    }

    /**
     * Data scope:
     * - servis/manager/superadmin: semua data
     * - lainnya: hanya data milik user login (user_id)
     */
    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();

        if ($user?->hasAnyRole(['servis', 'manager', 'koordinator teknisi', 'superadmin'])) {
            return parent::getEloquentQuery();
        }

        return parent::getEloquentQuery()
            ->where('user_id', $user?->id ?? 0);
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('staging', 'request')->count();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }

    // =======================
    // Helpers (akses & opsi)
    // =======================

    /**
     * Opsi staging yang boleh dipilih oleh user saat ini.
     */
    private static function allowedStagingOptionsForCurrentUser(): array
    {
        $user = Auth::user();

        if (!$user) {
            return [];
        }

        $allOptions = StagingEnum::options();

        if ($user->hasRole('superadmin')) {
            return $allOptions; // semua opsi
        }

        if ($user->hasRole('koordinator teknisi')) {
            // manager boleh termasuk approve
            return array_filter($allOptions, function ($label, $value) {
                return in_array($value, [
                    StagingEnum::REQUEST->value,
                    StagingEnum::CEK_KERUSAKAN->value,
                    StagingEnum::ADA_BIAYA->value,
                    StagingEnum::CLOSE->value,
                    StagingEnum::APPROVE->value,
                ], true);
            }, ARRAY_FILTER_USE_BOTH);
        }

        if ($user->hasRole('servis')) {
            // servis TIDAK boleh approve
            return array_filter($allOptions, function ($label, $value) {
                return in_array($value, [
                    StagingEnum::REQUEST->value,
                    StagingEnum::CEK_KERUSAKAN->value,
                    StagingEnum::ADA_BIAYA->value,
                    StagingEnum::CLOSE->value,
                ], true);
            }, ARRAY_FILTER_USE_BOTH);
        }

        // user biasa: tidak ada opsi (kolom juga disembunyikan)
        return [];
    }

    /**
     * Siapa yang boleh mengubah staging di tabel.
     */
    protected static function canUpdateStaging(): bool
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }

        return $user->hasAnyRole([
            'superadmin',
            'koordinator teknisi',
            'servis'
        ]) && !empty(self::allowedStagingOptionsForCurrentUser());
    }

    /**
     * Aturan siapa yang boleh mengedit record (form):
     * - servis & superadmin: boleh
     * - manager & user biasa: tidak
     */
    private static function canEditRecord(Service $record): bool
    {
        $user = Auth::user();

        if ($user?->hasRole('superadmin')) {
            return true;
        }

        if ($user?->hasRole('servis')) {
            return true;
        }

        // manager & user biasa tidak boleh edit
        return false;
    }

    /**
     * Siapa yang boleh hapus:
     * - user biasa & superadmin
     * - servis & manager: tidak
     * (Kalau mau batasi user hanya bisa hapus miliknya sendiri, aktifkan pengecekan owner)
     */
    private static function canDeleteRecord(Service $record): bool
    {
        $user = Auth::user();

        if ($user?->hasRole('superadmin')) {
            return true;
        }

        // "user tidak bisa edit hanya bisa hapus"
        if (! $user?->hasAnyRole(['servis', 'manager', 'koordinator teknisi', 'superadmin'])) {
            // opsional: pastikan hanya bisa hapus miliknya sendiri
            return (int) $record->user_id === (int) $user->id;
        }

        return false;
    }
    // Method untuk header actions (download)
    protected function getHeaderActions(): array
    {
        return [];
    }
}
