<?php

namespace App\Filament\Pickup\Resources;

use App\Filament\Pickup\Resources\PickupSmgResource\Pages;
use App\Models\PickupSmg;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\ActionGroup as ActionsActionGroup;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Filters\TrashedFilter;

class PickupSmgResource extends Resource
{
    protected static ?string $model = PickupSmg::class;

    protected static ?string $navigationIcon  = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationLabel = 'Pickup SMG';
    protected static ?string $modelLabel      = 'Pickup SMG';
    protected static ?string $pluralLabel     = 'Pickup SMG';
    protected static ?string $navigationGroup = 'Pickup';
    protected static ?int $navigationSort     = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Data Pickup SMG')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('perusahaan_id')
                        ->label('Perusahaan')
                        ->default(1)
                        ->required()
                        ->searchable()
                        ->preload()
                        ->options(
                            fn() => \App\Models\Company::query()
                                ->orderBy('nama_perusahaan')
                                ->pluck('nama_perusahaan', 'id')
                                ->all()
                        )
                        ->columnSpanFull(),

                    Forms\Components\DatePicker::make('tanggal_request')
                        ->label('Tanggal Request')
                        ->required()
                        ->native(false)
                        ->displayFormat('d M Y'),
                    Forms\Components\DatePicker::make('tanggal_pengambilan')
                        ->label('Tanggal Pengambilan')
                        ->native(false)
                        ->displayFormat('d M Y')
                        ->nullable(),

                    Forms\Components\TextInput::make('nama_supplier')
                        ->label('Nama Supplier')
                        ->required()
                        ->maxLength(200)
                        ->columnSpanFull(),

                    Forms\Components\Textarea::make('alamat_supplier')
                        ->label('Alamat Supplier')
                        ->rows(3)
                        ->nullable()
                        ->columnSpanFull(),

                    Forms\Components\Repeater::make('personil')
                        ->label('Personil')
                        ->addActionLabel('Tambah Personil')
                        ->defaultItems(0)
                        ->reorderable(false)
                        ->columns(2)
                        ->schema([
                            Forms\Components\TextInput::make('nama')
                                ->label('Nama')
                                ->required()
                                ->maxLength(150)
                                ->columnSpanFull(),
                        ])
                        ->columnSpanFull(),

                    Forms\Components\Select::make('status')
                        ->label('Status')
                        ->required()
                        ->options([
                            'pending' => 'Pending',
                            'done' => 'Done',
                        ])
                        ->default('pending'),

                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->poll('5s')
            ->columns([
                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Dibuat Oleh')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('perusahaan.nama_perusahaan')
                    ->label('Perusahaan')
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'CV Solusi Arya Prima' => 'danger',
                        'PT Sinergi Subur Makmur' => 'info',
                        default => 'primary',
                    }),

                Tables\Columns\TextColumn::make('tanggal_request')
                    ->label('Tgl Request')
                    ->date('d M Y'),

                Tables\Columns\TextColumn::make('nama_supplier')
                    ->label('Supplier')
                    ->searchable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('alamat_supplier')
                    ->label('Alamat Supplier')
                    ->wrap()
                    ->limit(50)
                    ->toggleable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'done',
                    ])
                    ->formatStateUsing(fn($state) => $state === 'done' ? 'Done' : 'Pending'),

                Tables\Columns\TextColumn::make('personil')
                    ->label('Personil')
                    ->getStateUsing(function ($record) {
                        $arr = $record->personil ?? [];
                        $names = collect($arr)->pluck('nama')->filter()->values();
                        if ($names->isEmpty()) return '-';
                        return $names->take(3)->implode(', ') . ($names->count() > 3 ? ' …' : '');
                    })
                    ->wrap()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('tanggal_pengambilan')
                    ->label('Tgl Pengambilan')
                    ->date('d M Y')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('updater.name')
                    ->label('Diubah Oleh')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d M Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'done' => 'Done',
                    ]),
                TrashedFilter::make()
                    ->visible(fn() => auth()->user()?->hasRole('superadmin')),
            ])
            ->actions([
                ActionsActionGroup::make([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make()
                        ->visible(function (PickupSmg $record) {
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
            'index'  => Pages\ListPickupSmgs::route('/'),
            'create' => Pages\CreatePickupSmg::route('/create'),
            'edit'   => Pages\EditPickupSmg::route('/{record}/edit'),
        ];
    }
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                \Illuminate\Database\Eloquent\SoftDeletingScope::class,
            ]);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }
}
