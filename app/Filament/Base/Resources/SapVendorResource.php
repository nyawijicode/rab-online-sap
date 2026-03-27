<?php

namespace App\Filament\Base\Resources;

use App\Filament\Base\Resources\SapVendorResource\Pages;
use App\Models\Sap\SapVendor;
use App\Services\Sap\SapHanaService;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SapVendorResource extends Resource
{
    protected static ?string $model = SapVendor::class;

    protected static ?string $navigationIcon  = 'heroicon-o-user-group';
    protected static ?string $navigationLabel = 'SAP Vendors';
    protected static ?string $label           = 'SAP Vendor';
    protected static ?string $navigationGroup = 'SAP';

    public static function form(\Filament\Forms\Form $form): \Filament\Forms\Form
    {
        return $form;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('CardCode')
                    ->label('Code')
                    ->searchable(),

                Tables\Columns\TextColumn::make('CardName')
                    ->label('Name')
                    ->wrap()
                    ->searchable(),

                Tables\Columns\TextColumn::make('CardType')
                    ->label('Type')
                    ->formatStateUsing(fn(?string $state) => match ($state) {
                        'S' => 'Supplier',
                        'C' => 'Customer',
                        'L' => 'Lead',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('Currency')
                    ->label('Currency')
                    ,

                Tables\Columns\TextColumn::make('Phone1')
                    ->label('Phone 1'),

                Tables\Columns\TextColumn::make('Cellular')
                    ->label('Mobile'),

                Tables\Columns\TextColumn::make('Email')
                    ->label('Email')
                    ->wrap(),
            ])
            ->filters([
                // Filter prefix code (VE, VB, dst.)
                Tables\Filters\SelectFilter::make('code_prefix')
                    ->label('Prefix Code')
                    ->options(
                        fn() => SapVendor::query()
                            ->selectRaw('SUBSTR("CardCode", 1, 2) as prefix')
                            ->groupBy('prefix')
                            ->orderBy('prefix')
                            ->pluck('prefix', 'prefix')
                            ->toArray()
                    )
                    ->query(function (Builder $query, $state) {
                        // $state bisa string, bisa array (misal ['value' => 'VE'])
                        $value = is_array($state)
                            ? ($state['value'] ?? null)
                            : $state;

                        if (! empty($value)) {
                            $query->where('CardCode', 'like', $value . '%');
                        }
                    }),

                // Opsional: filter tipe BP
                Tables\Filters\SelectFilter::make('CardType')
                    ->label('Tipe BP')
                    ->options([
                        'S' => 'Supplier',
                        'C' => 'Customer',
                        'L' => 'Lead',
                    ]),
            ])
            ->defaultSort('CardName', 'asc')
            ->actions([
                Tables\Actions\Action::make('detail')
                    ->label('Detail')
                    ->icon('heroicon-o-eye')
                    ->modalHeading('Detail Vendor')
                    ->modalWidth('2xl')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
                    ->modalContent(function (SapVendor $record) {
                        /** @var SapHanaService $service */
                        $service = app(SapHanaService::class);

                        $vendor = $service->getVendorByCode($record->CardCode);

                        return view('filament.base.sap.modals.vendor-detail', [
                            'vendor' => $vendor,
                        ]);
                    }),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSapVendors::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }
}
