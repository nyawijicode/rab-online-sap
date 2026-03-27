<?php

namespace App\Models\Sap;

use App\Models\QcTask;
use App\Models\User;
use App\Services\Sap\SapHanaService;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int|null $doc_entry
 * @property string|null $qc_no
 * @property string|null $item_code
 * @property string|null $item_name
 * @property int $qty
 * @property string|null $task_no
 * @property string|null $technician
 * @property string|null $status
 * @property bool $is_printed
 * @property \Illuminate\Support\Carbon|null $completed_at
 */
class MonitoringQc extends Model
{
    protected $table = 'monitoring_qcs';

    protected $fillable = [
        'doc_entry',
        'qc_no',
        'item_code',
        'item_name',
        'qty',
        'task_no',
        'technician',
        'status',
        'is_printed',
        'completed_at',
    ];

    protected $casts = [
        'is_printed' => 'boolean',
        'completed_at' => 'datetime',
        'qty' => 'integer',
        'doc_entry' => 'integer',
    ];

    /**
     * Sync method to populate this table from SAP and local DB.
     * This replaces the Sushi getRows() logic and avoids its limitations.
     */
    public static function syncData(): void
    {
        /** @var SapHanaService $service */
        $service = app(SapHanaService::class);

        // 1. Get Open QC headers from SAP
        $sapQcs = $service->getQualityChecks();
        $openSapQcs = collect($sapQcs)->where('Status', 'Open');

        // 2. Pre-fetch ALL pending/completed local tasks to avoid N+1
        // (No limit here because we are in a background-friendly sync context)
        $allLocalTasks = QcTask::whereNull('deleted_at')
            ->whereIn('status', ['pending', 'completed'])
            ->with('technician:id,name')
            ->get();

        $tasksByDocEntry = $allLocalTasks->groupBy('doc_entry');

        $newRows = [];

        // --- PART A: Add all active local tasks first ---
        foreach ($allLocalTasks as $task) {
            $newRows[] = [
                'doc_entry' => $task->doc_entry,
                'qc_no' => $task->qc_no,
                'item_code' => $task->item_code,
                'item_name' => $task->item_name,
                'qty' => 1,
                'task_no' => $task->task_no,
                'technician' => $task->technician?->name,
                'status' => $task->status,
                'is_printed' => (bool)$task->is_printed,
                'completed_at' => $task->completed_at?->format('Y-m-d H:i:s'),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // --- PART B: Add "Open" items from SAP ---
        foreach ($openSapQcs as $sapQc) {
            $docEntry = (int) $sapQc['DocEntry'];
            $qcNo = $sapQc['QCNo'];

            // Get local tasks for this docEntry
            $localTasksForDoc = $tasksByDocEntry->get($docEntry) ?? collect();
            $tasksByLine = $localTasksForDoc->groupBy('base_line_id');

            // Skip if no lines in SAP (shouldn't happen but defensive)
            $qcDetail = $service->getQualityCheckDetail($docEntry);
            if (!isset($qcDetail['details'])) continue;

            foreach ($qcDetail['details'] as $line) {
                $lineId = $line['U_BASELINE'] ?? $line['LineID'] ?? $line['LineId'];
                $totalQty = (float) ($line['U_TOTALQTY'] ?? $line['U_GRPO_QTY'] ?? 0);

                $tasksForLine = $tasksByLine->get($lineId) ?? collect();
                $assignedQty = $tasksForLine->count();

                if ($assignedQty < $totalQty) {
                    $openQty = $totalQty - $assignedQty;
                    $newRows[] = [
                        'doc_entry' => $docEntry,
                        'qc_no' => $qcNo,
                        'item_code' => $line['U_ITEMCODE'] ?? $line['ItemCode'] ?? '',
                        'item_name' => $line['U_ITEMNAME'] ?? $line['ItemName'] ?? '',
                        'qty' => $openQty,
                        'task_no' => null,
                        'technician' => null,
                        'status' => 'open',
                        'is_printed' => false,
                        'completed_at' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
        }

        // 3. Atomically replace data
        \DB::transaction(function () use ($newRows) {
            self::query()->truncate();
            foreach (array_chunk($newRows, 50) as $chunk) {
                self::insert($chunk);
            }
        });
    }
}
