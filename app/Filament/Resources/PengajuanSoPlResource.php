<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PengajuanSoPlResource\Pages;
use App\Models\PengajuanSoPl;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Filters\TrashedFilter;
use Illuminate\Database\Eloquent\Builder;

class PengajuanSoPlResource extends Resource
{
    protected static ?string $model = PengajuanSoPl::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Pengajuan SO PL';
    protected static ?string $pluralLabel = 'Pengajuan SO PL';
    protected static ?string $modelLabel = 'Pengajuan SO PL';
    protected static ?string $navigationGroup = 'Request Sales';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([

                Forms\Components\Hidden::make('user_id')
                    ->default(fn() => auth()->id()),

                Section::make('Informasi Dinas')
                    ->schema([
                        TextInput::make('nama_dinas')
                            ->label('Nama Dinas')
                            ->required()
                            ->maxLength(191),

                        TextInput::make('no_so_pl')
                            ->label('No SO (PL)')
                            ->maxLength(100),
                    ])
                    ->collapsible()
                    ->columns(2),

                Section::make('Dokumen Lampiran')
                    ->schema([
                        FileUpload::make('upload_file_rab')
                            ->label('Upload File RAB')
                            ->directory('pengajuan-so-pl/rab')
                            ->downloadable()
                            ->openable()
                            ->preserveFilenames()
                            ->maxSize(10240),

                        FileUpload::make('upload_file_sp')
                            ->label('Upload File SP')
                            ->directory('pengajuan-so-pl/sp')
                            ->downloadable()
                            ->openable()
                            ->preserveFilenames()
                            ->maxSize(10240),

                        FileUpload::make('upload_file_npwp')
                            ->label('Upload File NPWP')
                            ->directory('pengajuan-so-pl/npwp')
                            ->downloadable()
                            ->openable()
                            ->preserveFilenames()
                            ->maxSize(10240),
                    ])
                    ->collapsible()
                    ->columns(3),

                Section::make('Informasi PIC')
                    ->schema([
                        TextInput::make('nama_pic')
                            ->label('Nama PIC')
                            ->maxLength(191),

                        TextInput::make('nomor_pic')
                            ->label('Nomor PIC')
                            ->tel()
                            ->maxLength(50),

                        Textarea::make('alamat_pengiriman')
                            ->label('Alamat Pengiriman')
                            ->rows(3)
                            ->columnSpanFull(),

                        Textarea::make('keterangan')
                            ->label('Keterangan')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->columns(2),

                Section::make('Status & Tanggal Respon')
                    ->schema([
                        Select::make('status')
                            ->label('Status')
                            ->options([
                                'pending' => 'Pending',
                                'proses'  => 'Proses',
                                'selesai' => 'Selesai',
                                'ditolak' => 'Ditolak',
                            ])
                            ->default('pending')
                            ->required()
                            ->native(false)
                            ->hidden(function () {
                                $user = auth()->user();

                                if (! $user) {
                                    return true;
                                }

                                // Hanya superadmin & cs yang boleh lihat field status
                                return ! $user->hasAnyRole(['superadmin', 'cs']);
                            }),

                        DateTimePicker::make('tanggal_respon')
                            ->label('Tanggal Respon')
                            ->hidden(function () {
                                $user = auth()->user();

                                if (! $user) {
                                    return true;
                                }

                                // Hanya superadmin & cs yang boleh lihat field tanggal_respon
                                return ! $user->hasAnyRole(['superadmin', 'cs']);
                            }),
                    ])
                    ->collapsible()
                    ->columns(2),

            ])
            ->columns(1);
    }


    public static function table(Table $table): Table
    {
        return $table
            ->poll('5s')
            ->columns([
                TextColumn::make('user.name')
                    ->label('Pemohon')
                    ->copyable()
                    ->searchable(),

                TextColumn::make('nama_dinas')
                    ->label('Nama Dinas')
                    ->copyable()
                    ->searchable(),

                TextInputColumn::make('no_so_pl')
                    ->label('No SO (PL)')
                    ->rules(['max:100'])
                    ->placeholder('Isi No SO')
                    ->searchable()
                    ->disabled(fn() => ! auth()->user()?->hasAnyRole(['superadmin', 'cs'])),

                TextColumn::make('nama_pic')
                    ->label('Nama PIC')
                    ->copyable()
                    ->searchable(),

                TextColumn::make('nomor_pic')
                    ->label('Nomor PIC')
                    ->copyable()
                    ->searchable(),

                TextColumn::make('keterangan')
                    ->label('Keterangan')
                    ->limit(200)
                    ->wrap()
                    ->copyable()
                    ->toggleable()
                    ->tooltip(fn($record) => $record->keterangan),
                TextColumn::make('file_rab')
                    ->label('RAB')
                    ->state(fn($record) => $record->upload_file_rab ? 'RAB' : '–')
                    ->badge()
                    ->color(fn($record) => $record->upload_file_rab ? 'info' : 'gray')
                    ->url(
                        fn($record) => $record->upload_file_rab
                            ? asset('storage/' . $record->upload_file_rab)
                            : null
                    )
                    ->openUrlInNewTab()
                    ->tooltip(fn($record) => $record->upload_file_rab ? $record->upload_file_rab : 'Tidak ada file'),

                TextColumn::make('file_sp')
                    ->label('SP')
                    ->state(fn($record) => $record->upload_file_sp ? 'SP' : '–')
                    ->badge()
                    ->color(fn($record) => $record->upload_file_sp ? 'primary' : 'gray')
                    ->url(
                        fn($record) => $record->upload_file_sp
                            ? asset('storage/' . $record->upload_file_sp)
                            : null
                    )
                    ->openUrlInNewTab()
                    ->tooltip(fn($record) => $record->upload_file_sp ? $record->upload_file_sp : 'Tidak ada file'),

                TextColumn::make('file_npwp')
                    ->label('NPWP')
                    ->state(fn($record) => $record->upload_file_npwp ? 'NPWP' : '–')
                    ->badge()
                    ->color(fn($record) => $record->upload_file_npwp ? 'success' : 'gray')
                    ->url(
                        fn($record) => $record->upload_file_npwp
                            ? asset('storage/' . $record->upload_file_npwp)
                            : null
                    )
                    ->openUrlInNewTab()
                    ->tooltip(fn($record) => $record->upload_file_npwp ? $record->upload_file_npwp : 'Tidak ada file'),
                // Untuk superadmin & cs: inline edit pakai SelectColumn
                SelectColumn::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Pending',
                        'proses'  => 'Proses',
                        'selesai' => 'Selesai',
                        'ditolak' => 'Ditolak',
                    ])
                    ->visible(fn() => auth()->user()?->hasAnyRole(['superadmin', 'cs']))
                    ->disablePlaceholderSelection(),

                // Untuk role lain: cuma lihat badge warna (read-only)
                TextColumn::make('status_badge')
                    ->label('Status')
                    ->copyable()
                    ->state(fn($record) => $record->status)
                    ->badge()
                    ->colors([
                        'warning' => 'pending',
                        'info'    => 'proses',
                        'success' => 'selesai',
                        'danger'  => 'ditolak',
                    ])
                    ->icons([
                        'heroicon-o-clock'        => 'pending',
                        'heroicon-o-arrow-path'   => 'proses',
                        'heroicon-o-check-circle' => 'selesai',
                        'heroicon-o-x-circle'     => 'ditolak',
                    ])
                    ->visible(fn() => ! auth()->user()?->hasAnyRole(['superadmin', 'cs'])),

                TextColumn::make('tanggal_respon')
                    ->label('Tanggal Respon')
                    ->copyable()
                    ->dateTime('d-m-Y H:i'),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->copyable()
                    ->dateTime('d-m-Y H:i')

                    ->toggleable(isToggledHiddenByDefault: true),

                // IconColumn::make('deleted_at')
                //     ->label('Status')
                //     ->boolean()
                //     ->trueIcon('heroicon-o-archive-box-x-mark')
                //     ->falseIcon('heroicon-o-check-circle')

                //     ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make()
                    ->visible(fn() => auth()->user()?->hasRole('superadmin')),
                // ==============================
                // FILTER STATUS
                // ==============================
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Pending',
                        'proses'  => 'Proses',
                        'selesai' => 'Selesai',
                        'ditolak' => 'Ditolak',
                    ])
                    ->attribute('status'),

                // ==============================
                // FILTER TANGGAL RESPON (DATE RANGE)
                // ==============================
                Tables\Filters\Filter::make('tanggal_respon')
                    ->label('Tanggal Respon')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('Dari Tanggal'),

                        Forms\Components\DatePicker::make('until')
                            ->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when(
                                $data['from'] ?? null,
                                fn($q, $date) => $q->whereDate('tanggal_respon', '>=', $date)
                            )
                            ->when(
                                $data['until'] ?? null,
                                fn($q, $date) => $q->whereDate('tanggal_respon', '<=', $date)
                            );
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\Action::make('history')
                    ->label('History')
                    ->icon('heroicon-o-clock')
                    ->color('info')
                    ->modalHeading('Log Aktivitas')
                    ->modalContent(fn($record) => view('filament.components.system-history-modal', ['record' => $record]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup'),
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('reset_so')
                        ->label('Reset SO & Status')
                        ->icon('heroicon-o-arrow-path')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->visible(fn() => auth()->user()?->hasRole('superadmin'))
                        ->action(function (PengajuanSoPl $record) {
                            $record->update([
                                'no_so_pl'       => null,
                                'tanggal_respon' => null,
                                'status'         => 'pending',
                            ]);
                        }),

                    Tables\Actions\Action::make('preview_dokumen')
                        ->label('Preview Dokumen')
                        ->icon('heroicon-o-eye')
                        ->color('gray')
                        ->slideOver()
                        ->modalWidth('screen')
                        ->modalHeading('Preview Dokumen SO PL')
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('Tutup')
                        ->modalContent(fn(PengajuanSoPl $record) => view('filament.components.so-pl-preview', [
                            'record' => $record,
                        ]))
                        ->closeModalByClickingAway(false),

                    // View biasa (kalau mau tetap ada)
                    Tables\Actions\ViewAction::make()
                        ->slideOver()
                        ->modalWidth('screen'),

                    Tables\Actions\EditAction::make()
                        ->visible(function ($record): bool {
                            $user = auth()->user();

                            if (! $user) {
                                return false;
                            }

                            // Kalau status sudah selesai, jangan tampilkan tombol Edit sama sekali
                            if ($record->status === 'selesai') {
                                return false;
                            }

                            // superadmin boleh edit semua record (selama belum selesai)
                            if ($user->hasRole('superadmin')) {
                                return true;
                            }

                            // Pemohon (yang buat pengajuan) boleh edit pengajuannya sendiri
                            if ($record->user_id === $user->id) {
                                return true;
                            }

                            // selain itu nggak boleh edit
                            return false;
                        })
                        ->disabled(function ($record): bool {
                            $user = auth()->user();

                            if (! $user) {
                                return true;
                            }

                            // Kalau status sudah selesai, siapapun TIDAK boleh edit (double safety)
                            if ($record->status === 'selesai') {
                                return true;
                            }

                            // Selain itu, kalau sudah lolos visible(), berarti boleh edit
                            return false;
                        }),

                    Tables\Actions\DeleteAction::make()
                        ->visible(function ($record): bool {
                            $user = auth()->user();

                            if (! $user) {
                                return false;
                            }

                            // Delete hanya untuk superadmin & cs
                            return $user->hasRole('superadmin') || $user->hasRole('cs');
                        })
                        ->disabled(function ($record): bool {
                            $user = auth()->user();

                            if (! $user) {
                                return true;
                            }

                            // superadmin selalu boleh delete
                            if ($user->hasRole('superadmin')) {
                                return false;
                            }

                            // cs tidak boleh delete kalau status sudah selesai
                            if ($user->hasRole('cs') && $record->status === 'selesai') {
                                return true;
                            }

                            return false;
                        }),

                    Tables\Actions\ForceDeleteAction::make()
                        ->visible(function ($record): bool {
                            $user = auth()->user();

                            if (! $user) {
                                return false;
                            }

                            // Force delete hanya superadmin
                            return $user->hasRole('superadmin');
                        }),

                    Tables\Actions\RestoreAction::make()
                        ->visible(function ($record): bool {
                            $user = auth()->user();

                            if (! $user) {
                                return false;
                            }

                            // Restore hanya superadmin
                            return $user->hasRole('superadmin');
                        }),
                ]),
            ])
            ->actionsPosition(\Filament\Tables\Enums\ActionsPosition::BeforeColumns)
            ->defaultSort('created_at', 'desc')
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    /**
     * Query global untuk tabel (support soft deletes filter).
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery();
    }

    public static function canEdit($record): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        // Kalau status sudah selesai, siapapun TIDAK boleh edit
        if ($record->status === 'selesai') {
            return false;
        }

        // superadmin boleh edit semua selama status != selesai
        if ($user->hasRole('superadmin')) {
            return true;
        }

        // Pemohon (yang buat pengajuan) boleh edit pengajuannya sendiri
        if ($record->user_id === $user->id) {
            return true;
        }

        // Selain itu tidak boleh edit
        return false;
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListPengajuanSoPls::route('/'),
            'create' => Pages\CreatePengajuanSoPl::route('/create'),
            'edit'   => Pages\EditPengajuanSoPl::route('/{record}/edit'),
        ];
    }
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'pending')->count();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }
}
