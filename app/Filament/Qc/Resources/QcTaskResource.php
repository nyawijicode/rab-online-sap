<?php

namespace App\Filament\Qc\Resources;

use App\Filament\Qc\Resources\QcTaskResource\Pages;
use App\Models\QcTask;
use App\Models\QcCriteria;
use App\Models\QcTaskCriteria;
use App\Services\Sap\SapHanaService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;

class QcTaskResource extends Resource
{
    protected static ?string $model = QcTask::class;

    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';
    protected static ?string $navigationLabel = 'Tugas QC';
    protected static ?string $label = 'Tugas QC';
    protected static ?string $navigationGroup = 'QC';

    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();
        $query = parent::getEloquentQuery();

        if (!$user->hasRole(['superadmin', 'koordinator teknisi', 'manager'])) {
            $query->where('technician_id', $user->id);

            // If on index page, group by qc_no to show one row per task group
            if (request()->routeIs('filament.qc.resources.qc-tasks.index')) {
                $query->select('qc_tasks.qc_no', 'qc_tasks.technician_id', 'qc_tasks.doc_entry')
                    ->selectRaw('MAX(id) as id') // Filament needs an ID
                    ->selectRaw('MAX(status) as status')
                    ->selectRaw('MAX(assigned_at) as assigned_at')
                    ->selectRaw('MAX(completed_at) as completed_at')
                    ->selectRaw('COUNT(*) as total_items')
                    ->groupBy('qc_no', 'technician_id', 'doc_entry');
            }
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('General Information')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('qc_no')
                                    ->label('QC No')
                                    ->disabled()
                                    ->dehydrated(true),
                                Forms\Components\TextInput::make('item_count')
                                    ->label('Total Item')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->placeholder(fn($record) => $record ? QcTask::where('qc_no', $record->qc_no)->where('technician_id', $record->technician_id)->count() : 0),
                            ]),
                    ]),

                Forms\Components\Section::make('Scan & Process')
                    ->schema([
                        // Global Scanner for the group
                        Forms\Components\Group::make([
                            Forms\Components\TextInput::make('global_scan')
                                ->label('Scan Serial Number (Global)')
                                ->placeholder('Scan SN untuk Item SAP Strict...')
                                ->helperText('Hanya berlaku untuk item yang memiliki nomor seri di SAP.')
                                ->autocomplete('off')
                                ->suffixAction(
                                    Forms\Components\Actions\Action::make('scan')
                                        ->icon('heroicon-o-camera')
                                        ->modalHeading('Scan Barcode')
                                        ->modalContent(view('filament.components.scanner'))
                                        ->modalSubmitAction(false)
                                        ->modalCancelAction(false)
                                        ->action(function ($set) {
                                            // Handled by JS
                                        })
                                        ->extraAttributes([
                                            'onclick' => "window.scannerTarget = 'data.global_scan'",
                                        ])
                                )
                                ->reactive()
                                ->afterStateUpdated(function ($state, $set, $get, $record) {
                                    if (empty($state)) return;

                                    $docEntry = $record->doc_entry;
                                    $sapService = app(SapHanaService::class);
                                    $qcDetail = $sapService->getQualityCheckDetail($docEntry);

                                    if (!$qcDetail || empty($qcDetail['serials'])) {
                                        $set('scan_error', "Data Serial Number tidak ditemukan di SAP untuk QC ini.");
                                        return;
                                    }

                                    $sapSerials = collect($qcDetail['serials']);

                                    // 1. Find matching SN in SAP Pool (support suffix match "hampir sama")
                                    $matchedSapSN = $sapSerials->first(function ($s) use ($state) {
                                        $sapSN = $s['U_SNBN'] ?? '';
                                        return ($sapSN === $state) || (!empty($sapSN) && (str_ends_with($sapSN, $state) || str_ends_with($state, $sapSN)));
                                    });

                                    if (!$matchedSapSN) {
                                        $set('scan_error', "Serial Number {$state} tidak ditemukan dalam daftar SAP untuk QC ini.");
                                        return;
                                    }

                                    $canonicalSN = $matchedSapSN['U_SNBN'];
                                    $itemCode = $matchedSapSN['U_ITEMCODE'];

                                    // 2. Global Uniqueness Check
                                    $isAlreadyScanned = QcTask::where('doc_entry', $docEntry)
                                        ->where('scanned_serial_number', $canonicalSN)
                                        ->exists();

                                    if ($isAlreadyScanned) {
                                        $set('scan_error', "Serial Number {$canonicalSN} sudah dipindai sebelumnya.");
                                        return;
                                    }

                                    // 3. Find available slot in the STRICT items list ONLY
                                    $items = $get('items_strict') ?? [];
                                    $found = false;

                                    // Check if this SN is already in the current FORM (strict list)
                                    foreach ($items as $item) {
                                        if (($item['scanned_serial_number'] ?? '') === $canonicalSN) {
                                            $set('scan_error', "Serial Number {$canonicalSN} sudah ada di form.");
                                            return;
                                        }
                                    }

                                    foreach ($items as $key => $item) {
                                        if ($item['item_code'] === $itemCode && empty($item['scanned_serial_number'])) {
                                            $set("items_strict.{$key}.scanned_serial_number", $canonicalSN);
                                            $set("items_strict.{$key}.serial_number", $canonicalSN);
                                            $found = true;
                                            break;
                                        }
                                    }

                                    if (!$found) {
                                        $set('scan_error', "Tidak ada slot kosong untuk Item {$itemCode} di bagian SAP Strict.");
                                    } else {
                                        $set('scan_error', null);
                                        $set('global_scan', ''); // Clear for next scan
                                    }
                                }),
                            Forms\Components\Placeholder::make('scan_error_display')
                                ->content(fn($get) => new \Illuminate\Support\HtmlString("<span class='text-danger-600 font-bold'>{$get('scan_error')}</span>"))
                                ->visible(fn($get) => !empty($get('scan_error'))),
                        ])->columnSpanFull(),

                        Forms\Components\Section::make('Items with SAP SN (Strict)')
                            ->description('Item ini wajib dipindai sesuai dengan data Serial Number dari SAP.')
                            ->schema([
                                Forms\Components\Repeater::make('items_strict')
                                    ->label('Strict Item List')
                                    ->schema([
                                        Forms\Components\Hidden::make('id'),
                                        Forms\Components\Grid::make(4)
                                            ->schema([
                                                Forms\Components\TextInput::make('task_no')
                                                    ->label('Task No')
                                                    ->disabled(),
                                                Forms\Components\TextInput::make('item_code')
                                                    ->label('Item Code')
                                                    ->disabled(),
                                                Forms\Components\TextInput::make('item_name')
                                                    ->label('Item Name')
                                                    ->disabled()
                                                    ->columnSpan(2),
                                            ]),

                                        Forms\Components\Grid::make(4)
                                            ->schema([
                                                Forms\Components\TextInput::make('serial_number')
                                                    ->label('SAP SN')
                                                    ->disabled()
                                                    ->dehydrated(true),
                                                Forms\Components\TextInput::make('scanned_serial_number')
                                                    ->label('Scanned SN')
                                                    ->required()
                                                    ->readonly()
                                                    ->live()
                                                    ->suffixAction(
                                                        Forms\Components\Actions\Action::make('row_scan')
                                                            ->icon('heroicon-o-camera')
                                                            ->modalHeading('Scan Barcode for this unit')
                                                            ->modalContent(view('filament.components.scanner'))
                                                            ->modalSubmitAction(false)
                                                            ->modalCancelAction(false)
                                                            ->extraAttributes(fn($component) => [
                                                                'onclick' => "window.scannerTarget = '{$component->getStatePath()}'",
                                                            ])
                                                    )
                                                    ->afterStateUpdated(function ($state, $set, $get, $record) {
                                                        if (empty($state)) return;
                                                        $docEntry = $record->doc_entry;
                                                        $sapService = app(SapHanaService::class);
                                                        $qcDetail = $sapService->getQualityCheckDetail($docEntry);
                                                        if (!$qcDetail || empty($qcDetail['serials'])) return;

                                                        $sapSerials = collect($qcDetail['serials']);
                                                        $matchedSapSN = $sapSerials->first(function ($s) use ($state) {
                                                            $sapSN = $s['U_SNBN'] ?? '';
                                                            return ($sapSN === $state) || (!empty($sapSN) && (str_ends_with($sapSN, $state) || str_ends_with($state, $sapSN)));
                                                        });

                                                        if ($matchedSapSN) {
                                                            $canonicalSN = $matchedSapSN['U_SNBN'];
                                                            $set('scanned_serial_number', $canonicalSN);
                                                            $set('serial_number', $canonicalSN);
                                                        }
                                                    })
                                                    ->rules([
                                                        fn($get): \Closure => function (string $attribute, $value, \Closure $fail) use ($get) {
                                                            // 1. Local Duplicate Check within current form state
                                                            $itemsStrict = $get('../../items_strict') ?? [];
                                                            $itemsManual = $get('../../items_manual') ?? [];
                                                            $snList = collect($itemsStrict)
                                                                ->pluck('scanned_serial_number')
                                                                ->filter()
                                                                ->toArray();
                                                            $snList = array_merge($snList, collect($itemsManual)
                                                                ->pluck('scanned_serial_number')
                                                                ->filter()
                                                                ->toArray());

                                                            $counts = array_count_values($snList);
                                                            if (($counts[$value] ?? 0) > 1) {
                                                                $fail("Serial Number {$value} duplikat dalam daftar ini.");
                                                                return;
                                                            }

                                                            // 2. Global Uniqueness Check (was this SN already scanned by ANY technician for this doc_entry?)
                                                            // We can't easily get doc_entry here via $get relative path if we are deep,
                                                            // but we can assume validation on save will handle global check if this fails.
                                                            // For immediate feedback, we can use a more global $get or access the record.
                                                        },
                                                    ]),
                                                Forms\Components\TextInput::make('qty_pass')
                                                    ->label('Selesai Ok')
                                                    ->numeric()
                                                    ->default(1),
                                                Forms\Components\TextInput::make('qty_fail')
                                                    ->label('Selesai Rusak')
                                                    ->numeric()
                                                    ->default(0),
                                            ]),

                                        Forms\Components\Select::make('condition')
                                            ->options([
                                                'ok' => 'OK',
                                                'broken' => 'Rusak',
                                            ])
                                            ->required()
                                            ->hidden(fn($get) => empty($get('scanned_serial_number'))),

                                        Forms\Components\Section::make('Checklist & Detail')
                                            ->hidden(fn($get) => empty($get('scanned_serial_number')))
                                            ->schema([
                                                Forms\Components\Textarea::make('reason')
                                                    ->label('Catatan/Alasan'),
                                                Forms\Components\Grid::make(2)
                                                    ->schema(function () {
                                                        $criteria = QcCriteria::all();
                                                        $fields = [];
                                                        foreach ($criteria as $item) {
                                                            $fields[] = Forms\Components\Checkbox::make('criteria_' . $item->id)
                                                                ->label($item->name)
                                                                ->statePath('checklist.' . $item->id);
                                                        }
                                                        return $fields;
                                                    }),
                                                Forms\Components\Repeater::make('attachments')
                                                    ->schema([
                                                        Forms\Components\FileUpload::make('file')->image()->directory('qc-attachments'),
                                                        Forms\Components\TextInput::make('caption'),
                                                    ])->columns(2)->collapsible(),
                                            ]),
                                    ])
                                    ->addable(false)
                                    ->deletable(false)
                                    ->columns(1),
                            ]),

                        Forms\Components\Section::make('Items without SAP SN (Manual Entry)')
                            ->description('Item ini tidak memiliki data Serial Number di SAP. Masukkan atau pindai Serial Number secara manual.')
                            ->schema([
                                Forms\Components\Repeater::make('items_manual')
                                    ->label('Manual Item List')
                                    ->schema([
                                        Forms\Components\Hidden::make('id'),
                                        Forms\Components\Grid::make(4)
                                            ->schema([
                                                Forms\Components\TextInput::make('task_no')
                                                    ->label('Task No')
                                                    ->disabled(),
                                                Forms\Components\TextInput::make('item_code')
                                                    ->label('Item Code')
                                                    ->disabled(),
                                                Forms\Components\TextInput::make('item_name')
                                                    ->label('Item Name')
                                                    ->disabled()
                                                    ->columnSpan(2),
                                            ]),

                                        Forms\Components\Grid::make(4)
                                            ->schema([
                                                Forms\Components\TextInput::make('serial_number')
                                                    ->label('SAP SN')
                                                    ->disabled()
                                                    ->dehydrated(true)
                                                    ->placeholder('No SAP SN'),
                                                Forms\Components\TextInput::make('scanned_serial_number')
                                                    ->label('Input/Scan SN')
                                                    ->live()
                                                    ->suffixAction(
                                                        Forms\Components\Actions\Action::make('row_scan_manual')
                                                            ->icon('heroicon-o-camera')
                                                            ->modalHeading('Scan Barcode')
                                                            ->modalContent(view('filament.components.scanner'))
                                                            ->modalSubmitAction(false)
                                                            ->modalCancelAction(false)
                                                            ->extraAttributes(fn($component) => [
                                                                'onclick' => "window.scannerTarget = '{$component->getStatePath()}'",
                                                            ])
                                                    )
                                                    ->rules([
                                                        fn($get): \Closure => function (string $attribute, $value, \Closure $fail) use ($get) {
                                                            // 1. Local Duplicate Check within current form state
                                                            $itemsStrict = $get('../../items_strict') ?? [];
                                                            $itemsManual = $get('../../items_manual') ?? [];
                                                            $snList = collect($itemsStrict)
                                                                ->pluck('scanned_serial_number')
                                                                ->filter()
                                                                ->toArray();
                                                            $snList = array_merge($snList, collect($itemsManual)
                                                                ->pluck('scanned_serial_number')
                                                                ->filter()
                                                                ->toArray());

                                                            $counts = array_count_values($snList);
                                                            if (($counts[$value] ?? 0) > 1) {
                                                                $fail("Serial Number {$value} duplikat dalam daftar ini.");
                                                                return;
                                                            }
                                                        },
                                                    ]),
                                                Forms\Components\TextInput::make('qty_pass')
                                                    ->label('Selesai Ok')
                                                    ->numeric()
                                                    ->default(1),
                                                Forms\Components\TextInput::make('qty_fail')
                                                    ->label('Selesai Rusak')
                                                    ->numeric()
                                                    ->default(0),
                                            ]),

                                        Forms\Components\Select::make('condition')
                                            ->options([
                                                'ok' => 'OK',
                                                'broken' => 'Rusak',
                                            ])
                                            ->required()
                                            ->hidden(fn($get) => empty($get('scanned_serial_number'))),

                                        Forms\Components\Section::make('Checklist & Detail')
                                            ->hidden(fn($get) => empty($get('scanned_serial_number')))
                                            ->schema([
                                                Forms\Components\Textarea::make('reason')
                                                    ->label('Catatan/Alasan'),
                                                Forms\Components\Grid::make(4)
                                                    ->schema(function () {
                                                        $criteria = QcCriteria::all();
                                                        $fields = [];
                                                        foreach ($criteria as $item) {
                                                            $fields[] = Forms\Components\Checkbox::make('criteria_' . $item->id)
                                                                ->label($item->name)
                                                                ->statePath('checklist.' . $item->id);
                                                        }
                                                        return $fields;
                                                    }),
                                                Forms\Components\Repeater::make('attachments')
                                                    ->schema([
                                                        Forms\Components\FileUpload::make('file')->image()->directory('qc-attachments'),
                                                        Forms\Components\TextInput::make('caption'),
                                                    ])->columns(2)->collapsible(),
                                            ]),
                                    ])
                                    ->addable(false)
                                    ->deletable(false)
                                    ->columns(1),
                            ]),
                    ]),
            ]);
    }


    public static function table(Table $table): Table
    {
        return $table
            ->poll('5s')
            ->columns([
                Tables\Columns\TextColumn::make('task_no')
                    ->label('Task No')
                    ->searchable(),
                Tables\Columns\TextColumn::make('qc_no')
                    ->label('QC No'),
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
                Tables\Columns\TextColumn::make('assigned_at')
                    ->label('Ditugaskan')
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
                Tables\Columns\TextColumn::make('completed_at')
                    ->label('Selesai')
                    ->dateTime('d-m-Y H:i'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'completed' => 'Completed',
                    ])
                    ->default('pending'),
            ])
            ->actions([
                ActionGroup::make([
                    Tables\Actions\EditAction::make()
                        ->label('Process')
                        ->icon('heroicon-o-pencil-square')
                        ->visible(fn($record) => $record->status === 'pending')
                        ->color('success'),

                    Tables\Actions\Action::make('print_label')
                        ->label('Print Label')
                        ->icon('heroicon-o-printer')
                        ->color('info')
                        ->url(fn(QcTask $record) => route('qc.labels.print', ['ids' => $record->id]))
                        ->after(function (QcTask $record) {
                            $record->update(['is_printed' => true]);
                        })
                        ->visible(fn(QcTask $record) => $record->status === 'completed'),

                    Tables\Actions\ViewAction::make()->color('warning')->icon('heroicon-o-eye'),
                    Tables\Actions\DeleteAction::make()
                        ->visible(fn() => Auth::user()->hasRole(['superadmin', 'koordinator teknisi'])),
                    Tables\Actions\RestoreAction::make()
                        ->visible(fn() => Auth::user()->hasRole(['superadmin', 'koordinator teknisi'])),
                    Tables\Actions\ForceDeleteAction::make()
                        ->visible(fn() => Auth::user()->hasRole(['superadmin', 'koordinator teknisi'])),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('print_labels')
                        ->label('Print Labels')
                        ->icon('heroicon-o-printer')
                        ->action(function (\Illuminate\Support\Collection $records) {
                            $records->each->update(['is_printed' => true]);
                            return redirect()->route('qc.labels.print', ['ids' => $records->pluck('id')->implode(',')]);
                        }),
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn() => Auth::user()->hasRole(['superadmin', 'koordinator teknisi'])),
                    Tables\Actions\RestoreBulkAction::make()
                        ->visible(fn() => Auth::user()->hasRole(['superadmin', 'koordinator teknisi'])),
                    Tables\Actions\ForceDeleteBulkAction::make()
                        ->visible(fn() => Auth::user()->hasRole(['superadmin', 'koordinator teknisi'])),
                ]),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'completed' => 'Completed',
                    ])
                    ->default('pending'),
                Tables\Filters\TrashedFilter::make()
                    ->visible(fn() => Auth::user()->hasRole(['superadmin', 'koordinator teknisi'])),
            ])
            ->actionsPosition(\Filament\Tables\Enums\ActionsPosition::BeforeColumns)
            ->defaultSort('task_no', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQcTasks::route('/'),
            'edit' => Pages\EditQcTask::route('/{record}/edit'),
            'view' => Pages\ViewQcTask::route('/{record}'),
        ];
    }
}
