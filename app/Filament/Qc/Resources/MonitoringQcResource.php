<?php

namespace App\Filament\Qc\Resources;

use App\Filament\Qc\Resources\MonitoringQcResource\Pages;
use App\Models\Sap\MonitoringQc;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class MonitoringQcResource extends Resource
{
    protected static ?string $model = MonitoringQc::class;

    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';
    protected static ?string $navigationLabel = 'Monitoring QC';
    protected static ?string $label = 'Monitoring QC';
    protected static ?string $navigationGroup = 'QC';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Detail Item')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('qc_no')->label('QC No'),
                                Forms\Components\TextInput::make('item_code')->label('Item Code'),
                                Forms\Components\TextInput::make('qty')->label('Qty'),
                                Forms\Components\TextInput::make('item_name')->label('Item Name')->columnSpan(3),
                                Forms\Components\TextInput::make('task_no')->label('Task No'),
                                Forms\Components\TextInput::make('technician')->label('Teknisi'),
                                Forms\Components\TextInput::make('status')->label('Status'),
                                Forms\Components\DateTimePicker::make('completed_at')->label('Selesai pada'),
                            ]),
                    ])->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('task_no')
                    ->label('Task No')
                    ->searchable()

                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('technician')
                    ->label('Teknisi')
                    ->searchable()

                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('qc_no')
                    ->label('QC No')
                    ->searchable(),
                Tables\Columns\TextColumn::make('item_code')
                    ->label('Item Code')
                    ->searchable(),
                Tables\Columns\TextColumn::make('item_name')
                    ->label('Item Name')
                    ->searchable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('qty')
                    ->label('Qty')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state) => match ($state) {
                        'open'      => 'gray',
                        'pending'   => 'warning',
                        'completed' => 'success',
                        default     => 'gray',
                    })
                    ->formatStateUsing(fn(string $state) => ucfirst($state)),
                Tables\Columns\IconColumn::make('is_printed')
                    ->label('Print')
                    ->boolean()
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('completed_at')
                    ->label('Selesai')
                    ->dateTime('d-m-Y H:i'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'open' => 'Open',
                        'pending' => 'Pending',
                        'completed' => 'Completed',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([])
            ->defaultSort('task_no', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMonitoringQcs::route('/'),
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
}
