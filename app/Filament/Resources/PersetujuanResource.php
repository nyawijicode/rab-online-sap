<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PersetujuanResource\Pages;
use App\Models\Persetujuan;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class PersetujuanResource extends Resource
{
    protected static ?string $model = Persetujuan::class;

    protected static ?string $navigationIcon  = 'heroicon-o-document-check';
    protected static ?string $navigationLabel = 'Persetujuan';
    protected static ?string $navigationGroup = 'Pengaturan';
    protected static ?string $label           = 'Persetujuan';
    protected static ?string $slug            = 'persetujuan';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Pengajuan Persetujuan')
                ->description('Pilih user dan daftarkan siapa saja yang harus menyetujui')
                ->schema([
                    Select::make('user_id')
                        ->label('User yang Diajukan')
                        ->relationship('user', 'name')
                        ->searchable()
                        ->required()
                        ->default(fn() => Auth::id())
                        // hanya superadmin boleh mengubah pemilik
                        ->disabled(fn() => ! Auth::user()?->hasRole('superadmin')),

                    Select::make('company')
                        ->label('Company')
                        ->options(function () {
                            return \App\Models\Company::pluck('nama_perusahaan', 'kode')->toArray();
                        })
                        ->required()
                        ->default('sap')
                        ->unique(
                            modifyRuleUsing: fn($rule, $get) => $rule->where('user_id', $get('user_id')),
                            ignoreRecord: true
                        ),

                    // ====== DAFTAR APPROVER ======
                    Repeater::make('approvers')
                        ->label('Daftar Approver')
                        ->relationship('approvers') // HasMany ke model detail approver untuk persetujuan ini
                        ->schema([
                            Select::make('approver_id')
                                ->label('Pilih Approver')
                                ->options(fn() => User::query()->pluck('name', 'id'))
                                ->searchable()
                                ->required(),
                            Select::make('divisi_id')
                                ->label('Divisi Approver (Opsional)')
                                ->options(fn() => \App\Models\Divisi::pluck('nama', 'id'))
                                ->searchable()
                                ->placeholder('Pilih Divisi jika berbeda dari global'),
                        ])
                        ->addActionLabel('Tambah Approver')
                        ->minItems(1)
                        ->columns(2)
                        ->required()
                        /**
                         * KUNCI: Simpan relasi dengan kontrol penuh per-role.
                         * - marcomm/rt: upsert item yang ada di form, lalu hapus hanya item yang benar2 dihapus di form
                         *   (hanya pada record ini), tidak pernah menyentuh pengajuan lain.
                         * - superadmin: full reconcile (hapus yang tidak ada di form) pada record ini.
                         */
                        ->saveRelationshipsUsing(function ($record, $state) {
                            $state = is_array($state) ? $state : [];

                            // 1. Ambil daftar data approver yang dipilih
                            $approverData = collect($state)
                                ->map(fn($row) => [
                                    'approver_id' => $row['approver_id'] ?? null,
                                    'divisi_id'   => $row['divisi_id'] ?? null,
                                ])
                                ->filter(fn($item) => $item['approver_id']);

                            // 2. Hapus SEMUA approver lama di record ini (bersih)
                            $record->approvers()->delete();

                            // 3. Buat ulang sesuai urutan / daftar baru
                            foreach ($approverData as $data) {
                                $record->approvers()->create([
                                    'approver_id' => $data['approver_id'],
                                    'divisi_id'   => $data['divisi_id'],
                                ]);
                            }
                        }),
                    // =================================

                    Grid::make(4)->schema([
                        self::autoToggle('menggunakan_teknisi', 'koordinator teknisi'),
                        self::autoToggle('asset_teknisi', 'rt'),
                        self::autoToggle('use_pengiriman', 'koordinator gudang'),
                        self::autoToggle('use_car', 'koordinator gudang'),
                        self::autoToggle('use_manager', 'manager'),
                        self::autoToggle('use_komisaris', 'komisaris'),
                        self::autoToggle('use_direktur', 'direktur'),
                        self::autoToggle('use_owner', 'owner'),
                    ]),
                ])
                ->columns(1),
        ]);
    }

    /**
     * Toggle yang menambah/menghapus approver otomatis sesuai role target.
     * Aktif untuk semua role, karena marcomm/rt boleh mengatur persetujuannya sendiri.
     * Perubahan tetap lokal pada form; saat save, penyimpanan terkontrol di saveRelationshipsUsing di atas.
     */
    public static function autoToggle(string $field, string $role)
    {
        return Toggle::make($field)
            ->label(ucwords(str_replace('_', ' ', $field)))
            ->default(false)
            ->onIcon('heroicon-s-check')
            ->offIcon('heroicon-s-x-mark')
            ->onColor('success')
            ->offColor('danger')
            ->reactive()
            ->afterStateUpdated(function ($state, callable $set, callable $get) use ($role) {
                $approvers = collect($get('approvers'));
                $user      = User::role($role)->first();
                if (! $user) {
                    return;
                }

                if ($state) {
                    if (! $approvers->contains('approver_id', $user->id)) {
                        $approvers->push(['approver_id' => $user->id]);
                    }
                } else {
                    $approvers = $approvers->reject(fn($item) => ($item['approver_id'] ?? null) == $user->id);
                }

                $set('approvers', $approvers->values()->all());
            });
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('user_id')
            ->columns([
                TextColumn::make('user.name')
                    ->label('User')
                    ->searchable(),

                TextColumn::make('user.status.cabang.nama')
                    ->label('Cabang')
                    ->sortable(),

                TextColumn::make('company')
                    ->label('Company')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'sap' => 'CV Solusi Arya Prima',
                        'ssm' => 'PT Sinergi Subur Makmur',
                        default => $state,
                    })
                    ->color(fn(string $state): string => match ($state) {
                        'sap' => 'info',
                        'ssm' => 'success',
                        default => 'gray',
                    }),

                TextColumn::make('approvers')
                    ->label('Approver')
                    ->getStateUsing(function ($record) {
                        return $record->approvers
                            ->map(fn($item) => $item->approver?->name)
                            ->filter()
                            ->join(', ');
                    }),

                TextColumn::make('menggunakan_teknisi')
                    ->label('Teknisi')
                    ->formatStateUsing(fn($state) => $state ? 'Ya' : 'Tidak')
                    ->badge()
                    ->color(fn($state) => $state ? 'success' : 'danger'),

                TextColumn::make('asset_teknisi')
                    ->label('RT')
                    ->formatStateUsing(fn($state) => $state ? 'Ya' : 'Tidak')
                    ->badge()
                    ->color(fn($state) => $state ? 'success' : 'danger'),

                TextColumn::make('use_pengiriman')
                    ->label('Gudang')
                    ->formatStateUsing(fn($state) => $state ? 'Ya' : 'Tidak')
                    ->badge()
                    ->color(fn($state) => $state ? 'success' : 'danger'),

                TextColumn::make('use_car')
                    ->label('Mobil')
                    ->formatStateUsing(fn($state) => $state ? 'Ya' : 'Tidak')
                    ->badge()
                    ->color(fn($state) => $state ? 'success' : 'danger'),

                TextColumn::make('use_manager')
                    ->label('Manager')
                    ->formatStateUsing(fn($state) => $state ? 'Ya' : 'Tidak')
                    ->badge()
                    ->color(fn($state) => $state ? 'success' : 'danger'),

                TextColumn::make('use_komisaris')
                    ->label('Komisaris')
                    ->formatStateUsing(fn($state) => $state ? 'Ya' : 'Tidak')
                    ->badge()
                    ->color(fn($state) => $state ? 'success' : 'danger'),

                TextColumn::make('use_direktur')
                    ->label('Direktur')
                    ->formatStateUsing(fn($state) => $state ? 'Ya' : 'Tidak')
                    ->badge()
                    ->color(fn($state) => $state ? 'success' : 'danger'),

                TextColumn::make('use_owner')
                    ->label('Owner')
                    ->formatStateUsing(fn($state) => $state ? 'Ya' : 'Tidak')
                    ->badge()
                    ->color(fn($state) => $state ? 'success' : 'danger'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('cabang')
                    ->label('Cabang')
                    ->relationship('user.status.cabang', 'kode')
                    ->searchable()
                    ->preload(),
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
                Tables\Actions\EditAction::make()
                    ->visible(fn($record) => Auth::user()?->hasRole('superadmin') || $record->user_id === Auth::id()),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListPersetujuans::route('/'),
            'create' => Pages\CreatePersetujuan::route('/create'),
            'edit'   => Pages\EditPersetujuan::route('/{record}/edit'),
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return Auth::check()
            && (Auth::user()->hasRole('superadmin')
                || Auth::user()->hasRole('marcomm')
                || Auth::user()->hasRole('rt')
                || Auth::user()->hasRole('koordinator teknisi')); // ⬅️ tambah ini
    }

    public static function canViewAny(): bool
    {
        return Auth::check()
            && (Auth::user()->hasRole('superadmin')
                || Auth::user()->hasRole('marcomm')
                || Auth::user()->hasRole('rt')
                || Auth::user()->hasRole('koordinator teknisi')); // ⬅️ tambah ini
    }

    /**
     * IZINKAN marcomm/rt membuat lebih dari satu pengajuan.
     * Pengajuan baru TIDAK akan mengubah pengajuan sebelumnya.
     */
    public static function canCreate(): bool
    {
        if (Auth::user()->hasRole('superadmin')) {
            return true;
        }

        if (
            Auth::user()->hasRole('marcomm')
            || Auth::user()->hasRole('rt')
            || Auth::user()->hasRole('koordinator teknisi')
        ) { // ⬅️ tambah ini
            return true;
        }

        return false;
    }

    public static function canEdit($record): bool
    {
        return Auth::check()
            && (Auth::user()->hasRole('superadmin') || $record->user_id === Auth::id());
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['approvers', 'user.status.cabang']);

        // Batasi marcomm/rt/koordinator teknisi hanya melihat miliknya sendiri
        if (
            Auth::user()?->hasRole('marcomm')
            || Auth::user()?->hasRole('rt')
            || Auth::user()?->hasRole('koordinator teknisi')
        ) { // ⬅️ tambah ini
            $query->where('user_id', Auth::id());
        }

        return $query;
    }
}
