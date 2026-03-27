<?php

namespace App\Filament\Resources;

use App\Enums\RequestMarcommKebutuhanEnum;
use App\Enums\RequestMarcommStatusEnum;
use App\Filament\Resources\RequestMarcommResource\Pages;
use App\Models\Pengajuan;
use App\Models\RequestMarcomm;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Enums\FiltersLayout;
use Carbon\Carbon;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\URL;
use App\Models\Company;
use App\Models\UserStatus;

class RequestMarcommResource extends Resource
{
    protected static ?string $model = RequestMarcomm::class;

    protected static ?string $navigationIcon  = 'heroicon-o-megaphone';
    protected static ?string $navigationLabel = 'Request Marcomm';
    protected static ?string $label           = 'Request Marcomm';
    protected static ?string $navigationGroup = 'Request Sales';
    protected static ?string $slug            = 'request-marcomm';
    protected static ?int $navigationSort     = 100;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Pemohon')
                    ->schema([
                        Forms\Components\Select::make('companies_id')
                            ->label('Perusahaan')
                            ->relationship('company', 'nama_perusahaan')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->default(1),
                        Forms\Components\TextInput::make('no_request')
                            ->label('No Request')
                            ->default(fn() => RequestMarcomm::generateNoRequest())
                            ->disabled()
                            ->dehydrated(),
                        Forms\Components\Hidden::make('user_id')
                            ->default(fn() => Auth::id()),
                        Forms\Components\TextInput::make('nama_pemohon')
                            ->label('Nama Pemohon')
                            ->default(fn() => Auth::user()?->name)
                            ->disabled()
                            ->required()
                            ->dehydrated(),
                        Forms\Components\TextInput::make('nama_atasan')
                            ->label('Nama Atasan')
                            ->default(function () {
                                // Ambil atasan langsung user login
                                $currentAtasan = \App\Models\User::find(
                                    auth()->user()->userStatus?->atasan_id
                                );

                                // Telusuri rantai atasan sampai ketemu role spv
                                while ($currentAtasan) {
                                    // Jika role-nya spv → return name
                                    if ($currentAtasan->hasRole('spv')) {
                                        return $currentAtasan->name;
                                    }

                                    // Lanjut ke atasannya lagi
                                    $currentAtasan = \App\Models\User::find(
                                        $currentAtasan->userStatus?->atasan_id
                                    );
                                }

                                return null; // kalau tidak ditemukan
                            })
                            ->disabled()
                            ->dehydrated(true),
                        Forms\Components\TextInput::make('jabatan')
                            ->label('Jabatan Pemohon')
                            ->maxLength(100),
                        Forms\Components\TextInput::make('kantor_cabang')
                            ->label('Kantor Cabang')
                            ->maxLength(150),
                        Forms\Components\TextInput::make('nomor_kantor')
                            ->label('Nomor Kantor')
                            ->tel()
                            ->maxLength(50),
                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->maxLength(150),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Kebutuhan Marcomm')
                    ->schema([
                        Forms\Components\CheckboxList::make('kebutuhan')
                            ->label('Kebutuhan')
                            ->options(RequestMarcommKebutuhanEnum::options())
                            ->columns(2)
                            ->required(),
                        Forms\Components\TextInput::make('quantity')
                            ->label('Quantity')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->required(),
                        Forms\Components\FileUpload::make('foto')
                            ->label('Foto (ID card, Lanyard, dll)')
                            ->disk('public')                     // 🔑 simpan di disk public
                            ->directory('request-marcomm')       // folder di dalam disk public
                            ->image()
                            ->imageEditor()
                            ->multiple()
                            ->maxFiles(10)
                            ->maxSize(10240)
                            ->visibility('public')
                            ->columnSpanFull()
                            ->nullable(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Status & Tanggal')
                    ->schema([
                        Forms\Components\DatePicker::make('tanggal_respon')
                            ->label('Tanggal Respon')
                            ->native(false)
                            ->displayFormat('d F Y')
                            ->nullable()
                            ->disabled(fn() => ! Auth::user()?->hasAnyRole(['superadmin', 'marcomm'])),
                        Forms\Components\Select::make('status')
                            ->label('Status Request')
                            ->options(RequestMarcommStatusEnum::options())
                            ->default(RequestMarcommStatusEnum::TUNGGU->value)
                            ->required()
                            ->disabled(function (Get $get) {
                                // hanya superadmin / marcomm yang boleh edit status
                                return ! Auth::user()?->hasAnyRole(['superadmin', 'marcomm']);
                            }),
                        Forms\Components\DatePicker::make('tanggal_terkirim')
                            ->label('Terkirim / Selesai pada Tanggal')
                            ->native(false)
                            ->displayFormat('d F Y')
                            ->nullable()
                            ->disabled(fn() => ! Auth::user()?->hasAnyRole(['superadmin', 'marcomm'])),
                    ])
                    ->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordUrl(null)     // 🔒 matikan URL baris (klik row nggak kemana-mana)
            ->recordAction(null)  // (opsional) tidak ada default action
            ->poll('5s')
            ->columns([
                Tables\Columns\TextColumn::make('no_request')
                    ->label('No Request')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('pengajuan.no_rab')
                    ->label('No RAB')
                    ->formatStateUsing(fn($state, RequestMarcomm $record) => $record->pengajuan?->no_rab ?? '-')
                    ->extraAttributes(function (RequestMarcomm $record) {
                        // bikin kelihatan seperti link ketika ada pengajuan
                        return $record->pengajuan_id
                            ? ['class' => 'text-primary-600 hover:text-primary-700 underline cursor-pointer']
                            : [];
                    })
                    ->tooltip(fn(RequestMarcomm $record) => $record->pengajuan_id ? 'Preview RAB' : null)
                    ->action(
                        Action::make('preview_rab')
                            ->label('Preview PDF')
                            ->icon('heroicon-o-eye')
                            ->hidden(fn(RequestMarcomm $record) => ! $record->pengajuan_id) // sembunyikan kalau belum ter-link
                            ->slideOver()
                            ->modalWidth('screen')
                            ->modalHeading(
                                fn(RequestMarcomm $record) =>
                                'Preview RAB: ' . ($record->pengajuan?->no_rab ?? '-')
                            )
                            ->modalSubmitAction(false)
                            ->modalCancelActionLabel('Tutup')
                            ->closeModalByClickingAway()
                            ->modalContent(function (RequestMarcomm $record) {
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

                                if (! $pengajuan) {
                                    return view('filament.components.blank', [
                                        'title'   => 'Pengajuan tidak ditemukan',
                                        'message' => 'Data pengajuan sudah dihapus atau tidak valid.',
                                    ]);
                                }

                                return view('filament.components.pdf-preview', [
                                    'record' => $pengajuan,
                                    'url'    => URL::signedRoute('pengajuan.pdf.preview', $pengajuan),
                                ]);
                            })
                    ),
                Tables\Columns\TextColumn::make('pemohon_detail')
                    ->label('Nama Pemohon')
                    ->state(fn($record) => "
    <span class='font-semibold'>{$record->nama_pemohon}</span><br>
    <span class='text-xs text-gray-600'>
        Jabatan: {$record->jabatan}<br>
        Email: {$record->email}<br>
        No Kantor: {$record->nomor_kantor}<br>
        Atasan: {$record->nama_atasan}
    </span>
")
                    ->html()
                    ->wrap()
                    ->copyable()
                    ->lineClamp(6),
                Tables\Columns\TextColumn::make('kantor_cabang')
                    ->label('Kantor Cabang')
                    ->searchable(),
                Tables\Columns\TextColumn::make('kebutuhan')
                    ->label('Kebutuhan')
                    ->badge()
                    ->separator(', ')
                    ->getStateUsing(fn(RequestMarcomm $record) => $record->kebutuhanLabels()),
                Tables\Columns\TextColumn::make('quantity')
                    ->label('Qty')
                    ->numeric(),
                Tables\Columns\SelectColumn::make('status')
                    ->label('Status')
                    ->options(RequestMarcommStatusEnum::options())
                    ->disabled(function (RequestMarcomm $record) {
                        $user = Auth::user();
                        if (! $user) {
                            return true;
                        }

                        // ❌ kalau status sudah selesai, hanya superadmin yang boleh ubah
                        if (
                            $record->status instanceof RequestMarcommStatusEnum &&
                            $record->status->value === RequestMarcommStatusEnum::SELESAI->value &&
                            ! $user->hasRole('superadmin')
                        ) {
                            return true;
                        }

                        // default: hanya superadmin & marcomm yang boleh inline edit
                        return ! $user->hasAnyRole(['superadmin', 'marcomm']);
                    }),
                Tables\Columns\ImageColumn::make('foto')
                    ->label('Foto')
                    ->disk('public')        // sama dengan disk FileUpload
                    ->visibility('public')
                    ->getStateUsing(function (RequestMarcomm $record) {
                        $user = auth()->user();

                        if (! $user) {
                            return null;
                        }

                        // Hanya boleh lihat jika:
                        // - superadmin / marcomm, ATAU
                        // - dia sendiri yang membuat request
                        $bolehLihat = $user->hasAnyRole(['superadmin', 'marcomm'])
                            || $user->id === $record->user_id;

                        if (! $bolehLihat) {
                            return null;
                        }

                        // kolom foto di-cast ke array di model
                        $files = $record->foto;

                        if (is_array($files) && count($files) > 0) {
                            // ambil file pertama
                            return $files[0];
                        }

                        if (is_string($files) && $files !== '') {
                            return $files;
                        }

                        return null;
                    }),
                Tables\Columns\TextColumn::make('company.nama_perusahaan')
                    ->label('Perusahaan')
                    ->badge()
                    ->color('danger'),
                Tables\Columns\TextColumn::make('tanggal_respon')
                    ->label('Tgl Respon')
                    ->date('d M Y H:i')
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('tanggal_terkirim')
                    ->label('Tgl Terkirim')
                    ->date('d M Y H:i')
                    ->placeholder('-'),

            ])
            ->filters([
                TrashedFilter::make()
                    ->visible(fn() => Auth::user()?->hasRole('superadmin')),
                Filter::make('status')
                    ->label('Status')
                    ->form([
                        Forms\Components\Select::make('status')
                            ->label('Status Request')
                            ->options(RequestMarcommStatusEnum::options())
                            ->native(false)
                            ->placeholder('Semua Status'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['status'] ?? null,
                            fn(Builder $q, $status) => $q->where('status', $status),
                        );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (empty($data['status'])) {
                            return null;
                        }

                        $enum = RequestMarcommStatusEnum::tryFrom($data['status']);

                        return 'Status: ' . ($enum?->label() ?? $data['status']);
                    }),
                Filter::make('created_at_range')
                    ->label('Tanggal Dibuat')
                    ->form([
                        Forms\Components\Fieldset::make('Tanggal Dibuat')
                            ->schema([
                                Forms\Components\DatePicker::make('dari')
                                    ->label('Dari')
                                    ->native(false)
                                    ->displayFormat('d/m/Y'),
                                Forms\Components\DatePicker::make('sampai')
                                    ->label('Sampai')
                                    ->native(false)
                                    ->displayFormat('d/m/Y'),
                            ])
                            ->columns(2),
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
                        if (!empty($data['dari'])) {
                            $chips[] = 'Mulai ' . Carbon::parse($data['dari'])->translatedFormat('d M Y');
                        }
                        if (!empty($data['sampai'])) {
                            $chips[] = 'Sampai ' . Carbon::parse($data['sampai'])->translatedFormat('d M Y');
                        }

                        return $chips;
                    }),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->filtersFormColumns(2)
            ->filtersFormWidth(MaxWidth::FourExtraLarge)
            ->filtersFormMaxHeight('400px')
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('history')
                        ->label('History')
                        ->icon('heroicon-o-clock')
                        ->color('info')
                        ->modalHeading('Log Aktivitas')
                        ->modalContent(fn($record) => view('filament.components.system-history-modal', ['record' => $record]))
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('Tutup'),
                    Tables\Actions\ViewAction::make()
                        ->slideOver()
                        ->modalWidth('screen')
                        ->visible(
                            fn($record) =>
                            Auth::user()?->hasAnyRole(['superadmin', 'marcomm'])
                                || Auth::id() === $record->user_id
                        ),
                    Tables\Actions\EditAction::make()
                        ->visible(function (RequestMarcomm $record) {
                            $user = Auth::user();
                            if (! $user) {
                                return false;
                            }

                            // superadmin selalu boleh
                            if ($user->hasRole('superadmin')) {
                                return true;
                            }

                            // kalau status selesai → selain superadmin tidak boleh edit
                            if (
                                $record->status instanceof RequestMarcommStatusEnum &&
                                $record->status->value === RequestMarcommStatusEnum::SELESAI->value
                            ) {
                                return false;
                            }

                            // selain itu: hanya pemilik yang boleh edit
                            return $user->id === $record->user_id;
                        }),
                    Tables\Actions\DeleteAction::make()
                        ->visible(fn($record) => Auth::user()?->hasRole('superadmin') || Auth::id() === $record->user_id),
                ]),

            ])
            ->actionsPosition(\Filament\Tables\Enums\ActionsPosition::BeforeColumns)
            ->defaultSort('created_at', 'desc')
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->visible(fn() => Auth::user()?->hasAnyRole(['superadmin'])),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListRequestMarcomms::route('/'),
            'create' => Pages\CreateRequestMarcomm::route('/create'),
            'edit'   => Pages\EditRequestMarcomm::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', RequestMarcommStatusEnum::TUNGGU)->count();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }
    public static function getEloquentQuery(): Builder
    {
        $user  = Auth::user();

        // Kalau belum login → kosong
        if (! $user) {
            return parent::getEloquentQuery()->whereRaw('1 = 0');
        }

        $query = parent::getEloquentQuery();

        // Role yang boleh lihat semua
        if ($user->hasAnyRole(['superadmin', 'manager', 'marcomm'])) {
            return $query;
        }

        // Ambil bawahan
        $bawahanIds = UserStatus::where('atasan_id', $user->id)
            ->pluck('user_id')
            ->toArray();

        // Tanpa filter teknisi
        return $query->where(function ($q) use ($user, $bawahanIds) {
            $q
                ->where('user_id', $user->id)         // punya sendiri
                ->orWhereIn('user_id', $bawahanIds);  // punya bawahan
        });
    }
}
