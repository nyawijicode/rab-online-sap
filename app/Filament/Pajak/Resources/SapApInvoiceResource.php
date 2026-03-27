<?php

namespace App\Filament\Pajak\Resources;

use App\Filament\Pajak\Resources\SapApInvoiceResource\Pages;
use App\Filament\Pajak\Resources\SapApInvoiceResource\RelationManagers;
use App\Models\Sap\SapApInvoice;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SapApInvoiceResource extends Resource
{
    protected static ?string $model = SapApInvoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-currency-dollar';
    protected static ?string $navigationLabel = 'A/P Invoice';
    protected static ?string $pluralModelLabel = 'A/P Invoices';
    protected static ?string $modelLabel = 'A/P Invoice';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function infolist(\Filament\Infolists\Infolist $infolist): \Filament\Infolists\Infolist
    {
        return $infolist
            ->schema([
                \Filament\Infolists\Components\Section::make('Informasi A/P Invoice')
                    ->schema([
                        \Filament\Infolists\Components\TextEntry::make('DocNum')->label('No. AP Invoice'),
                        \Filament\Infolists\Components\TextEntry::make('DocStatus')
                            ->label('Status')
                            ->badge()
                            ->color(fn(?string $state): string|array|null => match ($state) {
                                'O' => 'warning',
                                'C' => 'success',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn(?string $state): string => match ($state) {
                                'O' => 'Open',
                                'C' => 'Closed',
                                default => $state,
                            }),
                        \Filament\Infolists\Components\TextEntry::make('CardCode')->label('Vendor Code'),
                        \Filament\Infolists\Components\TextEntry::make('CardName')->label('Vendor Name'),
                        \Filament\Infolists\Components\TextEntry::make('DocDate')->label('Doc Date')->date(),
                        \Filament\Infolists\Components\TextEntry::make('DocDueDate')->label('Due Date')->date(),
                        \Filament\Infolists\Components\TextEntry::make('DocTotal')->label('Total')->numeric(decimalPlaces: 2),
                        \Filament\Infolists\Components\TextEntry::make('FakturPajak')
                            ->label('Faktur Pajak')
                            ->badge()
                            ->color(fn($state): string|array|null => $state ? 'success' : 'danger')
                            ->placeholder('-Kosong-'),
                        \Filament\Infolists\Components\TextEntry::make('Comments')->label('Comments')->columnSpanFull(),
                    ])
                    ->columns(['default' => 2]),
            ]);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('DocEntry')
                    ->label('DocEntry')

                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('DocNum')
                    ->label('No. AP Invoice')

                    ->searchable(),
                Tables\Columns\TextColumn::make('CardCode')
                    ->label('Vendor Code')

                    ->searchable(),
                Tables\Columns\TextColumn::make('CardName')
                    ->label('Vendor Name')

                    ->searchable(),
                Tables\Columns\TextColumn::make('DocDate')
                    ->label('Doc Date')
                    ->date(),
                Tables\Columns\TextColumn::make('DocDueDate')
                    ->label('Due Date')
                    ->date(),
                Tables\Columns\TextColumn::make('DocTotal')
                    ->label('Total')
                    ->numeric(decimalPlaces: 2),
                Tables\Columns\TextColumn::make('FakturPajak')
                    ->label('Faktur Pajak')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Faktur Pajak berhasil disalin')
                    ->placeholder('-Kosong-')
                    ->badge()
                    ->color(fn($state): string|array|null => $state ? 'success' : 'danger'),
                Tables\Columns\TextColumn::make('DocStatus')
                    ->label('Status')
                    ->badge()
                    ->color(fn(?string $state): string|array|null => match ($state) {
                        'O' => 'warning',
                        'C' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(?string $state): string => match ($state) {
                        'O' => 'Open',
                        'C' => 'Closed',
                        default => $state,
                    }),
            ])
            ->filters([
                SelectFilter::make('DocStatus')
                    ->label('Status')
                    ->options([
                        'O' => 'Open',
                        'C' => 'Closed',
                    ]),
                Filter::make('FakturPajak')
                    ->label('Status Faktur Pajak')
                    ->form([
                        Forms\Components\Select::make('has_faktur')
                            ->label('Faktur Pajak')
                            ->options([
                                '1' => 'Sudah Ada',
                                '0' => 'Belum Ada',
                            ])
                            ->placeholder('Pilih status'),
                    ])
                    ->query(function ($query, array $data) {
                        if (isset($data['has_faktur'])) {
                            if ($data['has_faktur'] === '1') {
                                $query->whereNotNull('FakturPajak')->where('FakturPajak', '<>', '');
                            } elseif ($data['has_faktur'] === '0') {
                                $query->whereNull('FakturPajak')->orWhere('FakturPajak', '');
                            }
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->slideOver()->modalWidth('screen'),
            ])
            ->bulkActions([
                //
            ])
            ->defaultSort('DocEntry', 'desc');
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
            'index' => Pages\ListSapApInvoices::route('/'),

            'view' => Pages\ViewSapApInvoice::route('/{record}'),
        ];
    }
}
