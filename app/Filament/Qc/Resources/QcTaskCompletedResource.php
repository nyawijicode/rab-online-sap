<?php

namespace App\Filament\Qc\Resources;

use App\Filament\Qc\Resources\QcTaskCompletedResource\Pages;
use App\Models\QcTask;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class QcTaskCompletedResource extends Resource
{
    protected static ?string $model = QcTask::class;

    protected static ?string $navigationIcon = 'heroicon-o-check-badge';
    protected static ?string $navigationLabel = 'QC Selesai';
    protected static ?string $label = 'QC Selesai';
    protected static ?string $navigationGroup = 'QC';
    protected static ?int $navigationSort = 2;


    public static function getEloquentQuery(): Builder
    {
        // Only show completed tasks
        $query = parent::getEloquentQuery();

        // Apply same technician filtering as QcTaskResource if needed
        $user = Auth::user();
        if (!$user->hasRole(['superadmin', 'koordinator teknisi', 'manager'])) {
            $query->where('technician_id', $user->id);
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        // Reuse form from QcTaskResource or simplified read-only
        return $form
            ->schema(QcTaskResource::form($form)->getComponents())
            ->disabled(); // Make it read-only mostly
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('task_no')
                    ->label('Task No')
                    ->searchable(),
                Tables\Columns\TextColumn::make('qc_no')
                    ->label('QC No')
                    ->searchable(),
                Tables\Columns\TextColumn::make('technician.name')
                    ->label('Teknisi')
                    ->searchable()
                    ->visible(fn() => Auth::user()->hasRole(['superadmin', 'koordinator teknisi'])),
                Tables\Columns\TextColumn::make('item_code')
                    ->label('Item Code')
                    ->searchable(),
                Tables\Columns\TextColumn::make('item_name')
                    ->label('Item Name')
                    ->searchable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('serial_number')
                    ->label('SAP SN')
                    ->searchable(),
                Tables\Columns\TextColumn::make('scanned_serial_number')
                    ->label('Scan SN')
                    ->searchable(),
                Tables\Columns\TextColumn::make('qty_pass')
                    ->label('Selesai Ok')
                    ->numeric()
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('qty_fail')
                    ->label('Selesai Rusak')
                    ->numeric()
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('completed_at')
                    ->label('Selesai')
                    ->dateTime('d-m-Y H:i'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state) => match ($state) {
                        'pending'   => 'warning',
                        'completed' => 'success',
                        default     => 'gray',
                    }),
                Tables\Columns\IconColumn::make('is_printed')
                    ->label('Print')
                    ->boolean()
                    ->alignCenter(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'completed' => 'Completed',
                    ])
                    ->default('completed'),
            ])
            ->actions([
                ActionGroup::make([
                    Tables\Actions\Action::make('print_label')
                        ->label('Print Label')
                        ->icon('heroicon-o-printer')
                        ->color('info')
                        ->url(fn(QcTask $record) => route('qc.labels.print', ['ids' => $record->id])),
                ])
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('print_labels')
                        ->label('Print Labels')
                        ->icon('heroicon-o-printer')
                        ->action(fn(\Illuminate\Support\Collection $records) => redirect()->route('qc.labels.print', ['ids' => $records->pluck('id')->implode(',')])),
                ]),
            ])
            ->defaultSort('task_no', 'desc')
            ->actionsPosition(\Filament\Tables\Enums\ActionsPosition::BeforeColumns);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQcTaskCompleteds::route('/'),
        ];
    }
}
