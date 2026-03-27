<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RequestTeknisiResource\Pages;
use App\Models\Pengajuan;
use App\Models\RequestTeknisi;
use App\Models\ProjectMonitor;
use App\Models\User;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use Filament\Tables\Actions\Action;
use Filament\Forms\ComponentContainer;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Toggle;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TrashedFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use App\Models\UserStatus;
use App\Models\Company;

class RequestTeknisiResource extends Resource
{
    protected static ?string $model = RequestTeknisi::class;

    protected static ?string $navigationIcon  = 'heroicon-o-wrench';
    protected static ?string $navigationLabel = 'Request Teknisi';
    protected static ?string $label           = 'Request Teknisi';
    protected static ?string $navigationGroup = 'Request Sales'; // SAMA dgn ServiceResource
    protected static ?string $slug            = 'request-teknisi';
    protected static ?int $navigationSort = 99;


    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Informasi Request Teknisi')->schema([

                Forms\Components\Section::make('Informasi Paket')->schema([
                    // === PILIH PERUSAHAAN (Simpan KODE) ===
                    Forms\Components\Select::make('company_code')
                        ->label('Perusahaan')
                        ->options(fn() => Company::pluck('nama_perusahaan', 'kode')) // Simpan kode, tampil nama
                        ->searchable()
                        ->preload()
                        ->required()
                        ->default('sap')
                        ->live()
                        ->columnSpanFull()
                        ->disabled(fn(Forms\Get $get) => $get('status') === 'selesai' && ! Auth::user()->hasRole('superadmin')),

                    Toggle::make('closing')
                        ->label('Sudah closing?')
                        ->inline(false)
                        ->onIcon('heroicon-s-check')
                        ->offIcon('heroicon-s-x-mark')
                        ->onColor('success')
                        ->offColor('danger')
                        ->default(true)
                        ->live()
                        ->columnSpanFull()
                        ->dehydrated()
                        ->helperText('Nonaktifkan jika belum closing.'),

                    // === ID PAKET (SAP): Dropdown ProjectMonitor ===
                    Forms\Components\Select::make('id_paket')
                        ->label('ID Paket')
                        ->searchable()
                        ->reactive()
                        ->hidden(fn(Forms\Get $get) => $get('company_code') !== 'sap') // Hanya tampil jika SAP
                        ->getSearchResultsUsing(function (string $search) {
                            return ProjectMonitor::query()
                                ->where(function ($q) use ($search) {
                                    $q->where('name', 'ILIKE', "%{$search}%")
                                        ->orWhere('code', 'ILIKE', "%{$search}%");
                                })
                                ->limit(50)
                                ->get()
                                ->mapWithKeys(fn($p) => [$p->code => "{$p->name}"])
                                ->toArray();
                        })
                        ->getOptionLabelUsing(function ($value) {
                            if (!$value) return null;
                            $p = ProjectMonitor::query()->where('code', $value)->first();
                            return $p ? "{$p->name}" : $value;
                        })
                        ->dehydrated(true)
                        ->required(fn(Forms\Get $get) => (bool) $get('closing'))
                        ->default(fn(Forms\Get $get) => $get('closing') ? null : 'Belum Closing')
                        ->afterStateHydrated(function ($component, $state, Forms\Get $get) {
                            if (! $get('closing')) {
                                if (blank($state)) {
                                    $component->state('Belum Closing');
                                }
                            }
                        })
                        ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                            // Auto-fill Nama Dinas khusus SAP
                            if (! $state || ! $get('closing')) return;
                            $project = ProjectMonitor::query()->where('code', $state)->with('customer')->first();
                            $set('nama_dinas', $project?->customer?->name ?: 'Belum Closing');
                        })
                        ->disabled(
                            fn(Forms\Get $get) =>
                            ! $get('closing') || ($get('status') === 'selesai' && ! Auth::user()->hasRole('superadmin'))
                        )
                        ->dehydrateStateUsing(function ($state, Forms\Get $get) {
                            return $get('closing') ? $state : 'Belum Closing';
                        }),

                    // === ID PAKET (Non-SAP / SSM): Input Manual ===
                    Forms\Components\TextInput::make('id_paket_manual')
                        ->label('ID Paket / Kode Proyek')
                        ->hidden(fn(Forms\Get $get) => $get('company_code') === 'sap') // Sembunyi jika SAP
                        ->required(fn(Forms\Get $get) => (bool) $get('closing'))
                        ->dehydrated(false) // Virtual
                        ->live()
                        ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                            // Manual input langsung set ke id_paket utama
                            $set('id_paket', $state ?: ($get('closing') ? null : 'Belum Closing'));
                        })
                        ->afterStateHydrated(function ($component, $state, Forms\Get $get) {
                            // Isi field manual dengan value yang ada
                            $idPaket = $get('id_paket');
                            if ($idPaket && $idPaket !== 'Belum Closing') {
                                $component->state($idPaket);
                            }
                        })
                        ->nullable()
                        ->maxLength(255)
                        ->disabled(
                            fn(Forms\Get $get) =>
                            ! $get('closing') || ($get('status') === 'selesai' && ! Auth::user()->hasRole('superadmin'))
                        )
                        ->dehydrateStateUsing(function ($state, Forms\Get $get) {
                            return $get('closing') ? $state : 'Belum Closing';
                        }),

                    // Hidden field untuk menampung nilai id_paket akhir
                    Forms\Components\Hidden::make('id_paket')
                        ->dehydrated(true),

                    Forms\Components\TextInput::make('nama_dinas')
                        ->label('Nama Dinas')
                        ->disabled(function (Forms\Get $get) {
                            if ($get('status') === 'selesai' && ! Auth::user()->hasRole('superadmin')) {
                                return true;
                            }
                            // SAP & closing → disabled (karena auto-fill), selain itu boleh edit
                            $isSap = ($get('company_code') === 'sap');
                            return $isSap && (bool) $get('closing');
                        })
                        ->dehydrated()
                        ->required()
                        ->maxLength(255),

                ])->columns(2),

                Forms\Components\Section::make('Kontak Informasi')->schema([
                    Forms\Components\TextInput::make('nama_kontak')->label('Nama Kontak')->required()->disabled(fn(\Filament\Forms\Get $get) => $get('status') === 'selesai' && ! Auth::user()->hasRole('superadmin')),
                    Forms\Components\TextInput::make('no_telepon')->label('No Telepon')->tel()->required()
                        ->disabled(fn(\Filament\Forms\Get $get) => $get('status') === 'selesai' && ! Auth::user()->hasRole('superadmin')),
                ])->columns(2),

                Forms\Components\Section::make('Detail Pekerjaan')->schema([
                    Forms\Components\Select::make('jenis_pekerjaan')
                        ->label('Jenis Pekerjaan')
                        ->options([
                            'Onsite Service' => 'Onsite Service',
                            'Uji Fungsi'     => 'Uji Fungsi',
                            'Instalasi'      => 'Instalasi',
                            'Survey'         => 'Survey',
                            'Visit'          => 'Visit',
                        ])->required()
                        ->disabled(fn(\Filament\Forms\Get $get) => $get('status') === 'selesai' && ! Auth::user()->hasRole('superadmin')),

                    Forms\Components\TextInput::make('cabang')->label('Cabang')->nullable()
                        ->disabled(fn(\Filament\Forms\Get $get) => $get('status') === 'selesai' && ! Auth::user()->hasRole('superadmin')),

                    Forms\Components\DatePicker::make('tanggal_pelaksanaan')
                        ->label('Tanggal Request')
                        ->displayFormat('d F Y')
                        ->native(false)
                        ->nullable()
                        ->disabled(fn(\Filament\Forms\Get $get) => $get('status') === 'selesai' && ! Auth::user()->hasRole('superadmin')),

                    // HANYA KOORDINATOR TEKNISI / SUPERADMIN
                    Forms\Components\DatePicker::make('tanggal_penjadwalan')
                        ->label('Tanggal Pelaksanaan')
                        ->displayFormat('d F Y')
                        ->native(false)
                        ->hidden(fn() => !Auth::user()->hasAnyRole(['superadmin', 'koordinator teknisi']))
                        ->nullable()
                        ->disabled(fn(\Filament\Forms\Get $get) => $get('status') === 'selesai' && ! Auth::user()->hasRole('superadmin')),

                    Forms\Components\Select::make('teknisis')
                        ->label('Pilih Teknisi (boleh lebih dari satu)')
                        ->relationship(
                            name: 'teknisis',
                            titleAttribute: 'name',
                            modifyQueryUsing: fn(EloquentBuilder $query) =>
                            $query->whereHas('roles', fn($roleQ) => $roleQ->where('name', 'teknisi'))
                        )
                        ->multiple()
                        ->preload()
                        ->searchable()
                        ->hidden(fn() => ! Auth::user()->hasAnyRole(['superadmin', 'koordinator teknisi']))
                        ->disabled(fn(Get $get) => $get('status') === 'selesai' && ! Auth::user()->hasRole('superadmin')),

                    Forms\Components\Select::make('status')
                        ->label('Status')
                        ->options([
                            'request'     => 'Request',
                            'penjadwalan' => 'Penjadwalan',
                            'progres'     => 'Progres',
                            'selesai'     => 'Selesai',
                            'ditolak'     => 'Ditolak',
                        ])
                        ->default('request')
                        ->disabled(function (\Filament\Forms\Get $get) {
                            $user = Auth::user();

                            // Kalau sudah selesai, hanya superadmin yang boleh ubah
                            if ($get('status') === 'selesai') {
                                return ! $user->hasRole('superadmin');
                            }

                            // Selain superadmin & koordinator teknisi => TETAP dikunci (baik create/edit)
                            return ! $user->hasAnyRole(['superadmin', 'koordinator teknisi']);
                        })
                        ->required(),

                    Forms\Components\Textarea::make('keterangan')->label('Keterangan/Uraian Pekerjaan')->nullable()->columnSpanFull(),

                    Forms\Components\Hidden::make('user_id')->default(fn() => Auth::id()),
                ])->columns(2),
            ])->columns(2),

            // ==== TEKNISI REPORT ====
            Forms\Components\Section::make('Teknisi Report')
                ->visible(function (?RequestTeknisi $record = null) {
                    $user = Auth::user();
                    if (! $user) {
                        return false;
                    }

                    // Selalu tampil untuk teknisi/koordinator teknisi/superadmin
                    if ($user->hasAnyRole(['teknisi', 'koordinator teknisi', 'superadmin', 'manager', 'hrd'])) {
                        return true;
                    }

                    // Untuk role "user" (pemohon): tampil HANYA jika sudah ada report
                    // Saat create ($record === null) -> otomatis false
                    if ($user->hasRole('user')) { // ganti ke 'pemohon' kalau nama rolenya berbeda
                        return $record?->reports()->exists() === true;
                    }

                    // Role lain: default sembunyikan
                    return false;
                })
                ->schema([
                    Forms\Components\Repeater::make('reports')
                        ->relationship()
                        // Kunci: jangan typehint ketat; saat create bisa null atau child model.
                        ->disabled(function ($record) {
                            // $record bisa: null | RequestTeknisi | RequestTeknisiReport
                            $rt = $record instanceof \App\Models\RequestTeknisi
                                ? $record
                                : ($record->request ?? null); // relasi di RequestTeknisiReport

                            return ! (
                                Auth::user()?->hasAnyRole(['superadmin', 'koordinator teknisi'])
                                || ($rt && $rt->isAssignedToUser((int) Auth::id()))
                            );
                        })
                        ->schema([
                            Forms\Components\FileUpload::make('foto')
                                ->directory('request-teknisi')
                                ->image()
                                ->visibility('public')
                                ->imageEditor()
                                ->maxSize(4096)
                                // ->disabled(function ($record) {
                                //     $rt = $record instanceof \App\Models\RequestTeknisi
                                //         ? $record
                                //         : ($record->request ?? null);

                                //     return ! (
                                //         Auth::user()?->hasAnyRole(['superadmin', 'koordinator teknisi'])
                                //         || ($rt && $rt->isAssignedToUser((int) Auth::id()))
                                //     );
                                // })
                                ->nullable(),

                            Forms\Components\Textarea::make('keterangan')
                                ->label('Keterangan')
                                // ->disabled(function ($record) {
                                //     $rt = $record instanceof \App\Models\RequestTeknisi
                                //         ? $record
                                //         : ($record->request ?? null);

                                //     return ! (
                                //         Auth::user()?->hasAnyRole(['superadmin', 'koordinator teknisi'])
                                //         || ($rt && $rt->isAssignedToUser((int) Auth::id()))
                                //     );
                                // })
                                ->nullable(),

                            Forms\Components\Hidden::make('user_id')
                                ->default(fn() => Auth::id()),
                        ])
                        ->label(false)
                        ->columnSpanFull()
                        ->addActionLabel('Tambah Report')
                        ->deletable(function ($record) {
                            $rt = $record instanceof \App\Models\RequestTeknisi ? $record : ($record->request ?? null);
                            return Auth::user()?->hasAnyRole(['superadmin', 'koordinator teknisi'])
                                || ($rt && $rt->isAssignedToUser((int) Auth::id()));
                        })
                        ->reorderable(function ($record) {
                            $rt = $record instanceof \App\Models\RequestTeknisi ? $record : ($record->request ?? null);
                            return Auth::user()?->hasAnyRole(['superadmin', 'koordinator teknisi'])
                                || ($rt && $rt->isAssignedToUser((int) Auth::id()));
                        }),
                ])
        ]);
    }

    public static function table(Table $table): Table
    {
        $user = Auth::user();
        $userIsAssigned = function ($record) use ($user) {
            // pastikan relasi teknisis sudah termuat; kalau belum, load minimal id saja
            $record->loadMissing('teknisis:id');
            return $record->isAssignedToUser($user->id);
        };
        $locked = fn($record) =>
        $record->status === 'selesai' && ! Auth::user()->hasRole('superadmin');
        $canEdit = fn($record) =>
        $user->hasAnyRole(['superadmin', 'koordinator teknisi'])
            || (int) $user->id === (int) $record->user_id      // pembuat
            || $userIsAssigned($record);                       // salah satu teknisi yg ditugaskan (pivot/legacy)

        $canFillReport = fn($record) =>
        $user->hasAnyRole(['superadmin', 'koordinator teknisi'])
            || $userIsAssigned($record);
        // NEW: yang boleh memutuskan = pemohon atau superadmin
        $canDecide = fn($record) =>
        $user->hasRole('superadmin')
            || ((int) $record->user_id === (int) $user->id);

        return $table
            ->recordAction(fn($record) => $locked($record) ? 'view' : ($canEdit($record) ? 'edit' : 'view'))
            ->recordUrl(null) // pakai recordAction saja
            ->poll('5s')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat Pada')
                    ->dateTime('d M Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('user.name')->label('Pemohon')->searchable(),
                Tables\Columns\TextColumn::make('company.nama_perusahaan')
                    ->label('Perusahaan')
                    ->badge()
                    ->color('danger')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('company_code')
                    ->label('Kode')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('no_request')->label('No Request')->description(fn(RequestTeknisi $record) => ('Keterangan : ' . ($record->keterangan ?? '-')))
                    ->searchable()->copyable()->limit(200)->wrap()->tooltip(fn($record) => $record->keterangan),
                Tables\Columns\TextColumn::make('no_rab')
                    ->label('No RAB')
                    ->formatStateUsing(fn($state) => $state ?: '-')
                    ->extraAttributes(function ($record) {
                        // bikin kelihatan seperti link ketika ada pengajuan
                        return $record->pengajuan_id
                            ? ['class' => 'text-primary-600 hover:text-primary-700 underline cursor-pointer']
                            : [];
                    })
                    // ->copyable()
                    ->tooltip(fn($record) => $record->pengajuan_id ? 'Preview RAB' : null)
                    ->action( // <<— inilah kuncinya
                        Action::make('preview_rab')
                            ->label('Preview PDF')
                            ->icon('heroicon-o-eye')
                            ->hidden(fn($record) => ! $record->pengajuan_id) // sembunyikan jika belum ter-link
                            ->slideOver()
                            ->modalWidth('screen')
                            ->modalHeading(fn($record) => 'Preview RAB: ' . ($record->no_rab ?: '-'))
                            ->modalSubmitAction(false)
                            ->modalCancelActionLabel('Tutup')
                            ->closeModalByClickingAway()
                            ->modalContent(function ($record) {
                                // muat pengajuan lengkap untuk preview
                                $pengajuan = Pengajuan::with([
                                    'lampiran',
                                    'lampiranAssets',
                                    'lampiranBiayaServices',
                                    'lampiranDinas',
                                    'lampiranPromosi',
                                    'lampiranKebutuhan',
                                    'lampiranKegiatan',
                                ])->find($record->pengajuan_id);

                                // jika tiba-tiba null, tampilkan pesan ringan di modal
                                if (! $pengajuan) {
                                    return view('filament.components.blank', [
                                        'title' => 'Pengajuan tidak ditemukan',
                                        'message' => 'Data pengajuan sudah dihapus atau tidak valid.',
                                    ]);
                                }

                                return view('filament.components.pdf-preview', [
                                    'record' => $pengajuan,
                                    'url'    => URL::signedRoute('pengajuan.pdf.preview', $pengajuan),
                                ]);
                            })
                    ),
                Tables\Columns\TextColumn::make('nama_dinas')->label('Nama Dinas/Kontak')->searchable()->copyable()->description(function (RequestTeknisi $record) {
                    $kontak = $record->nama_kontak ?? '-';
                    $telepon = $record->no_telepon ?? '-';
                    return "{$kontak}\n{$telepon}";
                })->limit(200)->wrap()->tooltip(fn($record) => $record->nama_dinas),
                Tables\Columns\TextColumn::make('jenis_pekerjaan')->label('Jenis')->copyable()->description(fn(RequestTeknisi $record) => ('ID Paket : ' . ($record->id_paket ?? '-')))->limit(200)->wrap()->tooltip(fn($record) => $record->id_paket),
                // Tables\Columns\TextColumn::make('nama_kontak')->label('Kontak'),
                // Tables\Columns\TextColumn::make('no_telepon')->label('Telepon'),
                Tables\Columns\TextColumn::make('tanggal_pelaksanaan')->label('Request')->date('d M Y'),

                // TAMPILAN RAPI untuk user biasa (format sama dgn "Request")
                Tables\Columns\TextColumn::make('tanggal_penjadwalan_display')
                    ->label('Jadwal')
                    ->getStateUsing(fn($record) => $record->tanggal_penjadwalan)
                    ->date('d M Y')
                    ->placeholder('-')
                    ->visible(fn() => ! Auth::user()->hasAnyRole(['superadmin', 'koordinator teknisi'])),

                // INLINE-EDIT untuk superadmin/koordinator
                Tables\Columns\TextInputColumn::make('tanggal_penjadwalan')
                    ->label('Jadwal')
                    ->placeholder('dd-mm-yyyy')
                    // tampilkan ke user sebagai d-m-Y
                    ->getStateUsing(
                        fn($record) =>
                        $record->tanggal_penjadwalan
                            ? Carbon::parse($record->tanggal_penjadwalan)->format('d-m-Y')
                            : null
                    )
                    // validasi input user
                    ->rules(['nullable', 'date_format:d-m-Y'])
                    ->disabled(fn($record) => $record->status === 'selesai' && ! Auth::user()->hasRole('superadmin'))
                    ->visible(fn() => Auth::user()->hasAnyRole(['superadmin', 'koordinator teknisi']))
                    // 🔹 tambahkan logika ubah status otomatis
                    ->afterStateUpdated(function ($state, $record) {
                        // $state adalah nilai yang dimasukkan user (string 'd-m-Y' atau null)
                        if (!empty($state)) {
                            // jika tanggal diisi → ubah ke 'penjadwalan'
                            $record->updateQuietly(['status' => 'penjadwalan']);
                        } else {
                            // jika dikosongkan → kembalikan ke 'request'
                            $record->updateQuietly(['status' => 'request']);
                        }
                    }),

                Tables\Columns\TextColumn::make('teknisis.name')
                    ->label('Teknisi')
                    ->badge()
                    ->separator(', ')
                    ->limitList(3) // optional: kalau banyak, akan di-truncate
                    ->sortable(false)
                    ->searchable(),

                // ====== STATUS ======
                // Badge berwarna untuk user biasa
                Tables\Columns\TextColumn::make('status_badge')
                    ->label('Status')
                    ->getStateUsing(fn($record) => $record->status)
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'selesai'     => 'success',  // hijau
                        'progres'     => 'info',     // biru
                        'penjadwalan' => 'warning',  // kuning
                        'ditolak'     => 'danger',   // merah
                        default       => 'gray',     // 'request' → abu-abu
                    })
                    ->visible(fn() => ! Auth::user()->hasAnyRole(['superadmin', 'koordinator teknisi'])),

                // Editable hanya untuk yang berwenang
                Tables\Columns\SelectColumn::make('status')
                    ->label('Status')
                    ->options([
                        'request'     => 'Request',
                        'penjadwalan' => 'Penjadwalan',
                        'progres'     => 'Progres',
                        'selesai'     => 'Selesai',
                        'ditolak'     => 'Ditolak',
                    ])
                    ->hidden(fn() => ! Auth::user()->hasAnyRole(['superadmin', 'koordinator teknisi']))
                    ->disabled(
                        fn($record) =>
                        $record->status === 'selesai' && ! Auth::user()->hasRole('superadmin')
                    ),
                // --- setelah kolom Status editable/badge milikmu ---
                Tables\Columns\TextColumn::make('final_status')
                    ->label('Konfirmasi Selesai')
                    ->badge()
                    ->description(function (RequestTeknisi $record) {
                        // Description hanya muncul jika status = 'ditolak'
                        if ($record->final_status === 'ditolak') {
                            return 'Alasan : ' . ($record->rejection_reason ?? '-');
                        }

                        // Jika bukan 'ditolak', jangan tampilkan description
                        return null;
                    })
                    ->formatStateUsing(fn($state) => match ($state) {
                        'pending'   => 'Menunggu Persetujuan',
                        'disetujui' => 'Disetujui',
                        'ditolak'   => 'Ditolak',
                        default     => ucfirst($state ?: '-'),
                    })
                    ->color(fn($state) => match ($state) {
                        'disetujui' => 'success',
                        'ditolak'   => 'danger',
                        'pending'   => 'warning',
                        default     => 'gray',
                    }),

                Tables\Columns\TextColumn::make('finalized_at')
                    ->label('Tanggal Disetujui/Ditolak')
                    ->dateTime('d M Y H:i')
                    ->placeholder('-'),

                // Opsional: tampilkan alasan penolakan (toggleable)
                Tables\Columns\TextColumn::make('rejection_reason')
                    ->label('Alasan Penolakan')
                    ->limit(60)
                    ->toggleable(isToggledHiddenByDefault: true),
                // Tables\Columns\TextColumn::make('user.name')->label('Pemohon')->toggleable(isToggledHiddenByDefault: true),

            ])
            ->filters([
                TrashedFilter::make()
                    ->visible(fn() => Auth::user()->hasRole('superadmin')),
                Filter::make('status')
                    ->label('Status')
                    ->form([
                        Forms\Components\Select::make('status')
                            ->label('Pilih Status')
                            ->options([
                                'request'     => 'Request',
                                'penjadwalan' => 'Penjadwalan',
                                'progres'     => 'Progres',
                                'selesai'     => 'Selesai',
                                'ditolak'     => 'Ditolak',
                            ])
                            ->native(false)
                            ->placeholder('Semua Status'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['status'] ?? null,
                            fn(Builder $q, $status) => $q->where('status', $status)
                        );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (empty($data['status'])) return null;

                        $labels = [
                            'request'     => 'Request',
                            'penjadwalan' => 'Penjadwalan',
                            'progres'     => 'Progres',
                            'selesai'     => 'Selesai',
                            'ditolak'     => 'Ditolak',
                        ];

                        return 'Status: ' . ($labels[$data['status']] ?? ucfirst($data['status']));
                    }),
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
                    Tables\Actions\RestoreAction::make()
                        ->visible(
                            fn($record) =>
                            auth()->user()->hasRole('superadmin') && $record->trashed()
                        ),
                    // === Isi Teknisi Report (tanpa masuk edit) ===
                    Tables\Actions\Action::make('isi_teknisi_report')
                        ->label('Isi Teknisi Report')
                        ->icon('heroicon-o-clipboard-document-check')
                        ->visible(fn($record) => $canFillReport($record))
                        ->slideOver()
                        ->modalHeading(fn($record) => 'Isi Teknisi Report: ' . $record->no_request)
                        ->modalWidth('screen')
                        ->mountUsing(function ($form, $record) {
                            $existing = $record->reports()->latest()->get(['id', 'foto', 'keterangan'])
                                ->map(fn($r) => ['id' => $r->id, 'foto' => $r->foto, 'keterangan' => $r->keterangan])->toArray();
                            $form->fill(['existing_reports' => $existing, 'new_reports' => []]);
                        })
                        ->form([
                            \Filament\Forms\Components\Section::make('Report yang sudah diunggah')->schema([
                                \Filament\Forms\Components\Repeater::make('existing_reports')
                                    ->label(false)
                                    ->schema([
                                        \Filament\Forms\Components\FileUpload::make('foto')->directory('request-teknisi')->image()->downloadable()->disabled(),
                                        \Filament\Forms\Components\Textarea::make('keterangan')->rows(2)->disabled(),
                                    ])
                                    ->deletable(false)->reorderable(false)->addable(false)->collapsed()->columnSpanFull(),
                            ])->collapsible(),
                            \Filament\Forms\Components\Section::make('Tambah Report Baru')->schema([
                                \Filament\Forms\Components\Repeater::make('new_reports')
                                    ->label(false)->minItems(1)->addActionLabel('Tambah Report')->schema([
                                        \Filament\Forms\Components\FileUpload::make('foto')->directory('request-teknisi')->image()->imageEditor()->maxSize(4096)->required(),
                                        \Filament\Forms\Components\Textarea::make('keterangan')->rows(3)->nullable(),
                                    ])->columnSpanFull(),
                            ]),
                        ])
                        ->action(function ($record, array $data) {
                            $items  = $data['new_reports'] ?? [];
                            $userId = Auth::id();
                            $count  = 0;
                            foreach ($items as $i) {
                                if (empty($i['foto']) && empty($i['keterangan'])) continue;
                                $record->reports()->create([
                                    'user_id' => $userId,
                                    'foto' => $i['foto'] ?? null,
                                    'keterangan' => $i['keterangan'] ?? null,
                                ]);
                                $count++;
                            }
                            if ($count > 0 && $record->status !== 'progres') {
                                $record->updateQuietly(['status' => 'progres']);
                            }
                        })
                        ->successNotificationTitle('Teknisi report tersimpan'),
                    Tables\Actions\Action::make('kelola_teknisi')
                        ->label('Pilih Teknisi')
                        ->icon('heroicon-o-user-plus')
                        ->visible(fn() => $user->hasAnyRole(['superadmin', 'koordinator teknisi']))
                        ->form([
                            // BUKAN relationship(), cukup options()
                            Forms\Components\Select::make('teknisi_ids')
                                ->label('Pilih Teknisi')
                                ->options(fn() => User::role('teknisi')->orderBy('name')->pluck('name', 'id'))
                                ->multiple()
                                ->preload()
                                ->searchable()
                                ->required(),
                        ])
                        ->mountUsing(function ($form, $record) {
                            $form->fill([
                                // isi nilai awal dari pivot
                                'teknisi_ids' => $record->teknisis()->pluck('users.id')->all(),
                            ]);
                        })
                        ->action(function ($record, array $data) {
                            $ids = collect($data['teknisi_ids'] ?? [])->filter()->map(fn($v) => (int) $v)->all();
                            $record->teknisis()->sync($ids);
                        })
                        ->successNotificationTitle('Daftar teknisi tersimpan'),
                    Tables\Actions\Action::make('approve_final')
                        ->label('Setujui')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(function ($record) use ($canDecide) {
                            // tampil hanya jika:
                            // - user boleh memutuskan (pemohon/superadmin)
                            // - sudah ada minimal 1 report
                            // - belum ada keputusan final (pending)
                            return $canDecide($record)
                                && $record->reports()->exists()
                                && ($record->final_status === 'pending');
                        })
                        ->requiresConfirmation()
                        ->modalHeading(fn($record) => 'Setujui Request: ' . $record->no_request)
                        ->modalSubheading('Keputusan ini akan mengubah status menjadi SELESAI.')
                        ->action(function ($record) {
                            $record->updateQuietly([
                                'status'          => 'selesai',
                                'final_status'    => 'disetujui',
                                'finalized_at'    => now(),
                                'finalized_by'    => Auth::id(),
                                'rejection_reason' => null,
                            ]);
                        })
                        ->successNotificationTitle('Request disetujui & ditandai selesai'),

                    Tables\Actions\Action::make('reject_final')
                        ->label('Tolak')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->visible(function ($record) use ($canDecide) {
                            return $canDecide($record)
                                && $record->reports()->exists()
                                && ($record->final_status === 'pending');
                        })
                        ->form([
                            Forms\Components\Textarea::make('rejection_reason')
                                ->label('Alasan Penolakan')
                                ->required()
                                ->rows(4),
                        ])
                        ->modalHeading(fn($record) => 'Tolak Request: ' . $record->no_request)
                        ->action(function ($record, array $data) {
                            $reason = trim((string)($data['rejection_reason'] ?? ''));
                            $record->updateQuietly([
                                'status'           => 'ditolak',
                                'final_status'     => 'ditolak',
                                'finalized_at'     => now(),
                                'finalized_by'     => Auth::id(),
                                'rejection_reason' => $reason,
                            ]);
                        })
                        ->successNotificationTitle('Request ditolak'),
                    Tables\Actions\ViewAction::make()->slideOver()
                        ->modalWidth('screen'),
                    Tables\Actions\Action::make('history')
                        ->label('History')
                        ->icon('heroicon-o-clock')
                        ->color('info')
                        ->modalHeading('Log Aktivitas')
                        ->modalContent(fn($record) => view('filament.components.request-teknisi-history-modal', ['record' => $record]))
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('Tutup'),
                    Tables\Actions\EditAction::make()->visible(fn($record) => $canEdit($record)),
                    Tables\Actions\DeleteAction::make()
                        ->visible(function ($record) {
                            $user = Auth::user();
                            if (! $user) {
                                return false;
                            }

                            // 1) superadmin & koordinator teknisi boleh hapus apa saja
                            if ($user->hasAnyRole(['superadmin', 'koordinator teknisi'])) {
                                return true;
                            }

                            // 2) user biasa hanya boleh hapus request miliknya sendiri
                            return (int) $user->id === (int) $record->user_id;
                        }),
                ]),
            ])
            ->actionsPosition(\Filament\Tables\Enums\ActionsPosition::BeforeColumns)
            ->defaultSort('created_at', 'desc')
            ->bulkActions([Tables\Actions\DeleteBulkAction::make()
                ->visible(fn() => Auth::user()->hasRole('superadmin'))]);
    }

    public static function getRelations(): array
    {
        return [
            \App\Filament\Resources\RequestTeknisiResource\RelationManagers\HistoriesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListRequestTeknisis::route('/'),
            'create' => Pages\CreateRequestTeknisi::route('/create'),
            'edit'   => Pages\EditRequestTeknisi::route('/{record}/edit'),
        ];
    }
    public static function getNavigationBadge(): ?string
    {
        // gunakan query yang sudah di-scope oleh getEloquentQuery()
        return (string) static::getEloquentQuery()
            ->where('status', 'request')
            ->count();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }
    public static function getEloquentQuery(): Builder
    {
        $user  = Auth::user();

        // Kalau belum login, tidak tampilkan apa-apa
        if (! $user) {
            return parent::getEloquentQuery()->whereRaw('1 = 0');
        }

        $query = parent::getEloquentQuery();

        // 1) Role yang boleh lihat SEMUA data
        if ($user->hasAnyRole(['superadmin', 'manager', 'hrd', 'koordinator teknisi', 'koordinator gudang', 'servis'])) {
            return $query;
        }

        // 2) Ambil daftar bawahan dari tabel user_statuses
        $bawahanIds = UserStatus::where('atasan_id', $user->id)
            ->pluck('user_id')
            ->toArray();

        // 3) User biasa / teknisi:
        //    - request yang dia buat sendiri (user_id = dirinya)
        //    - request milik bawahan (user_id termasuk bawahan)
        //    - request yang dia ditugaskan sebagai teknisi (relasi teknisis)
        return $query->where(function ($q) use ($user, $bawahanIds) {
            $q
                // punya sendiri
                ->where('user_id', $user->id)

                // punya bawahan
                ->orWhereIn('user_id', $bawahanIds)

                // request di mana dia ditugaskan sebagai teknisi
                ->orWhereHas('teknisis', function ($sub) use ($user) {
                    $sub->where('users.id', $user->id);
                });
        });
    }
}
