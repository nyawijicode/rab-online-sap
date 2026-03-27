<?php

namespace App\Filament\Base\Resources;

use App\Filament\Base\Resources\SapPurchaseOrderResource\Pages;
use App\Models\Sap\SapPurchaseOrder;
use App\Services\Sap\SapHanaService;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SapPurchaseOrderResource extends Resource
{
    protected static ?string $model = SapPurchaseOrder::class;

    protected static ?string $navigationIcon  = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'SAP Purchase Orders';
    protected static ?string $label           = 'SAP Purchase Order';
    protected static ?string $navigationGroup = 'SAP';

    public static function form(Form $form): Form
    {
        // Kita tidak pernah create / edit PO di sini, jadi form kosong saja.
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('DocNum')
                    ->label('PO No.')
                    ->searchable(),

                Tables\Columns\TextColumn::make('CardCode')
                    ->label('Vendor Code')
                    ->searchable(),
                Tables\Columns\TextColumn::make('PackageId')
                    ->label('ID Paket')
                    ->getStateUsing(function (\App\Models\Sap\SapPurchaseOrder $record) {
                        /** @var \App\Services\Sap\SapHanaService $service */
                        $service = app(\App\Services\Sap\SapHanaService::class);

                        return $service->getPurchaseOrderPackageId((int) $record->DocEntry);
                    })
                    ->wrap()
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('CardName')
                    ->label('Vendor Name')
                    ->wrap()
                    ->searchable(),

                Tables\Columns\TextColumn::make('DocDate')
                    ->label('Doc Date')
                    ->date(),

                Tables\Columns\TextColumn::make('DocDueDate')
                    ->label('Delivery Date')
                    ->date(),

                Tables\Columns\TextColumn::make('DocTotal')
                    ->label('Total')
                    ->money('IDR'),

                Tables\Columns\TextColumn::make('DocStatus')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn(string $state) => $state === 'O' ? 'Open' : 'Closed')
                    ->color(fn(string $state) => $state === 'O' ? 'warning' : 'success'),
            ])
            ->actions([
                Tables\Actions\Action::make('detail')
                    ->label('Detail')
                    ->icon('heroicon-o-eye')
                    ->modalHeading('Detail Purchase Order')
                    ->modalWidth('2xl')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
                    // DI SINI perbaikan: pakai SapPurchaseOrder, BUKAN array
                    ->modalContent(function (SapPurchaseOrder $record) {
                        /** @var SapHanaService $service */
                        $service = app(SapHanaService::class);

                        // Gunakan DocEntry dari model (int)
                        $data = $service->getPurchaseOrderDetail((int) $record->DocEntry);

                        return view('filament.base.sap.modals.po-detail', [
                            'header' => $data['header'] ?? null,
                            'lines'  => $data['lines'] ?? [],
                        ]);
                    }),
            ])
            ->defaultSort('DocEntry', 'desc')
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSapPurchaseOrders::route('/'),
        ];
    }

    // Hanya view, tidak boleh create/edit/delete
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
