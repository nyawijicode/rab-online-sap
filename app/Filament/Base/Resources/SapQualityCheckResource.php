<?php

namespace App\Filament\Base\Resources;

use App\Filament\Base\Resources\SapQualityCheckResource\Pages;
use App\Models\Sap\SapQualityCheck;
use App\Services\Sap\SapHanaService;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SapQualityCheckResource extends Resource
{
    protected static ?string $model = SapQualityCheck::class;

    protected static ?string $navigationIcon  = 'heroicon-o-check-badge';
    protected static ?string $navigationLabel = 'SAP Quality Checks';
    protected static ?string $label           = 'SAP Quality Check';
    protected static ?string $navigationGroup = 'SAP';

    public static function form(\Filament\Forms\Form $form): \Filament\Forms\Form
    {
        return $form;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('QCNo')
                    ->label('QC No')
                    ->searchable(),

                Tables\Columns\TextColumn::make('GrpoNo')
                    ->label('GRPO No')
                    ->searchable(),

                Tables\Columns\TextColumn::make('Branch')
                    ->label('Branch'),

                Tables\Columns\TextColumn::make('QCDate')
                    ->label('QC Date')
                    ->date(),

                Tables\Columns\TextColumn::make('Status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state) => match ($state) {
                        'Closed'   => 'success',
                        'Canceled' => 'danger',
                        default    => 'warning',
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('detail')
                    ->label('Detail')
                    ->icon('heroicon-o-eye')
                    ->modalHeading('Detail Quality Check')
                    ->modalWidth('2xl')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
                    ->modalContent(function (SapQualityCheck $record) {
                        /** @var SapHanaService $service */
                        $service = app(SapHanaService::class);

                        // PENTING: pakai DocEntry (int), bukan QCNo
                        $qc = $service->getQualityCheckDetail((int) $record->DocEntry);

                        return view('filament.base.sap.modals.qc-detail', [
                            'qc' => $qc,
                        ]);
                    }),
            ])
            ->defaultSort('DocEntry', 'desc')
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSapQualityChecks::route('/'),
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
