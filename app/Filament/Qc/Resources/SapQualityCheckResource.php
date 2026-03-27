<?php

namespace App\Filament\Qc\Resources;

use App\Filament\Qc\Resources\SapQualityCheckResource\Pages;
use App\Models\Sap\SapQualityCheck;
use App\Models\QcTask;
use App\Models\User;
use App\Services\Sap\SapHanaService;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class SapQualityCheckResource extends Resource
{
    protected static ?string $model = SapQualityCheck::class;

    protected static ?string $navigationIcon  = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationLabel = 'Pembagian QC';
    protected static ?string $label           = 'Pembagian QC';
    protected static ?string $navigationGroup = 'Pembagian QC';

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
                    ->label('Branch')
                    ->searchable(),

                Tables\Columns\TextColumn::make('QCDate')
                    ->label('QC Date')
                    ->date('d-m-Y'),

                Tables\Columns\TextColumn::make('ItemCodes')
                    ->label('Item Codes')
                    ->searchable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('ItemNames')
                    ->label('Item Names')
                    ->searchable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('Status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state) => match ($state) {
                        'Open'   => 'warning',
                        'Closed' => 'success',
                        default  => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('Status')
                    ->options([
                        'Open' => 'Open',
                        'Closed' => 'Closed',
                    ])
                    ->default('Open'),
            ])
            ->actions([
                ActionGroup::make([
                    Tables\Actions\Action::make('assign')
                        ->label('Assign')
                        ->icon('heroicon-o-user-plus')
                        ->color('success')
                        ->modalHeading('Assign QC to Technician')
                        ->modalWidth('6xl')
                        ->form(function (SapQualityCheck $record) {
                            /** @var SapHanaService $service */
                            $service = app(SapHanaService::class);
                            $qcDetail = $service->getQualityCheckDetail((int) $record->DocEntry);

                            // Get already assigned tasks to calculate remaining qty
                            $assignedTasks = QcTask::where('doc_entry', $record->DocEntry)
                                ->get()
                                ->groupBy('base_line_id');

                            $itemOptions = [];
                            foreach ($qcDetail['details'] as $detail) {
                                $lineId = $detail['U_BASELINE'] ?? $detail['LineID'] ?? $detail['LineId'];
                                $totalQty = (float) ($detail['U_TOTALQTY'] ?? $detail['U_GRPO_QTY'] ?? 0);

                                $lineAssignedQty = QcTask::where('doc_entry', $record->DocEntry)
                                    ->where('base_line_id', $lineId)
                                    ->sum('qty');

                                $remainingQty = $totalQty - $lineAssignedQty;
                                if ($remainingQty <= 0) continue;

                                $itemOptions[] = [
                                    'line_id' => $lineId,
                                    'item_code' => $detail['U_ITEMCODE'] ?? $detail['ItemCode'] ?? '',
                                    'item_name' => $detail['U_ITEMNAME'] ?? $detail['ItemName'] ?? '',
                                    'remaining_qty' => $remainingQty,
                                    'item_display' => ($detail['U_ITEMCODE'] ?? '') . " - " . ($detail['U_ITEMNAME'] ?? '') . " (Sisa: {$remainingQty})",
                                ];
                            }

                            return [
                                Forms\Components\Repeater::make('technician_assignments')
                                    ->label('Penugasan Teknisi')
                                    ->schema([
                                        Forms\Components\Select::make('technician_id')
                                            ->label('Teknisi')
                                            ->options(User::role('teknisi')->pluck('name', 'id'))
                                            ->required()
                                            ->searchable(),

                                        Forms\Components\Repeater::make('items')
                                            ->label('Daftar Item')
                                            ->schema([
                                                Forms\Components\Hidden::make('line_id'),
                                                Forms\Components\Hidden::make('item_code'),
                                                Forms\Components\Hidden::make('item_name'),
                                                Forms\Components\Grid::make(4)
                                                    ->schema([
                                                        Forms\Components\TextInput::make('item_display')
                                                            ->label('Item')
                                                            ->disabled()
                                                            ->dehydrated(false)
                                                            ->columnSpan(3),
                                                        Forms\Components\TextInput::make('qty')
                                                            ->label('Qty')
                                                            ->numeric()
                                                            ->default(0)
                                                            ->rules([
                                                                fn($get): \Closure => function (string $attribute, $value, \Closure $fail) use ($get) {
                                                                    // Validation here is tricky because same line can be in multiple technician blocks
                                                                    // We'll do a final validation in the action logic or use a more complex reactive validation
                                                                },
                                                            ]),
                                                    ]),
                                            ])
                                            ->default($itemOptions)
                                            ->addable(false)
                                            ->deletable(false)
                                            ->columns(1),
                                    ])
                                    ->defaultItems(1)
                                    ->addActionLabel('Tambah Teknisi Lain'),
                            ];
                        })
                        ->action(function (array $data, SapQualityCheck $record) {
                            $coordinatorId = Auth::id();

                            // Group assignments by line to check total quantity
                            $totalsByLine = [];

                            foreach ($data['technician_assignments'] as $techAssignment) {
                                $technicianId = $techAssignment['technician_id'];

                                foreach ($techAssignment['items'] as $item) {
                                    $qty = (float) $item['qty'];
                                    if ($qty <= 0) continue;

                                    $lineId = $item['line_id'];
                                    $totalsByLine[$lineId] = ($totalsByLine[$lineId] ?? 0) + $qty;

                                    // Create tasks
                                    for ($i = 0; $i < $qty; $i++) {
                                        // Generate Task No: QC/YYMMDD/XXXXX
                                        $datePart = date('ymd');
                                        $yearPrefix = 'QC/' . date('y');

                                        // Get last number for the current year (including trashed)
                                        $lastTask = QcTask::withTrashed()
                                            ->where('task_no', 'like', $yearPrefix . '%')
                                            ->orderBy('id', 'desc') // Order by ID to get the absolute last created
                                            ->first();

                                        $lastNumber = 0;
                                        if ($lastTask) {
                                            $parts = explode('/', $lastTask->task_no);
                                            $lastNumber = (int) end($parts);
                                        }

                                        $newNumber = str_pad($lastNumber + 1, 5, '0', STR_PAD_LEFT);
                                        $taskNo = "QC/{$datePart}/{$newNumber}";

                                        QcTask::create([
                                            'doc_entry' => $record->DocEntry,
                                            'qc_no' => $record->QCNo,
                                            'task_no' => $taskNo,
                                            'base_line_id' => $lineId,
                                            'item_code' => $item['item_code'],
                                            'item_name' => $item['item_name'],
                                            'qty' => 1,
                                            'technician_id' => $technicianId,
                                            'coordinator_id' => $coordinatorId,
                                            'assigned_at' => now(),
                                            'status' => 'pending',
                                        ]);
                                    }
                                }
                            }

                            // Optional: Add a check if totals exceed remaining
                        })
                        ->visible(fn(SapQualityCheck $record) => $record->Status === 'Open'),

                    Tables\Actions\Action::make('detail')
                        ->label('Detail')
                        ->color('warning')
                        ->icon('heroicon-o-eye')
                        ->modalHeading('Detail Quality Check')
                        ->modalWidth('2xl')
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('Tutup')
                        ->modalContent(function (SapQualityCheck $record) {
                            /** @var SapHanaService $service */
                            $service = app(SapHanaService::class);
                            $qc = $service->getQualityCheckDetail((int) $record->DocEntry);

                            return view('filament.base.sap.modals.qc-detail', [
                                'qc' => $qc,
                            ]);
                        }),
                ])
            ])
            ->defaultSort('DocEntry', 'desc')
            ->actionsPosition(\Filament\Tables\Enums\ActionsPosition::BeforeColumns);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSapQualityChecks::route('/'),
        ];
    }
    public static function canViewAny(): bool
    {
        $user = Auth::user();

        return $user
            && (
                $user->hasRole('superadmin')
                || $user->hasRole('koordinator teknisi')
            );
    }
    public static function canView($record): bool
    {
        return static::canViewAny();
    }
}
