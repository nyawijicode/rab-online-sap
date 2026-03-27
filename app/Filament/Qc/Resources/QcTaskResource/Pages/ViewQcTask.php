<?php

namespace App\Filament\Qc\Resources\QcTaskResource\Pages;

use App\Filament\Qc\Resources\QcTaskResource;
use App\Models\QcTask;
use App\Models\QcTaskCriteria;
use Filament\Resources\Pages\ViewRecord;

class ViewQcTask extends ViewRecord
{
    protected static string $resource = QcTaskResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Fetch SAP details to identify serial-managed items
        $sapService = app(\App\Services\Sap\SapHanaService::class);
        $qcDetail = $sapService->getQualityCheckDetail($this->record->doc_entry);
        $serialManagedItemCodes = collect($qcDetail['serials'] ?? [])->pluck('U_ITEMCODE')->unique()->toArray();

        // Load all tasks in this group
        $tasks = QcTask::where('qc_no', $this->record->qc_no)
            ->where('technician_id', $this->record->technician_id)
            ->get();

        $items_strict = [];
        $items_manual = [];

        foreach ($tasks as $task) {
            $checklist = QcTaskCriteria::where('qc_task_id', $task->id)
                ->get()
                ->pluck('is_checked', 'qc_criteria_id')
                ->toArray();

            $item = [
                'id' => $task->id,
                'task_no' => $task->task_no,
                'item_code' => $task->item_code,
                'item_name' => $task->item_name,
                'serial_number' => $task->serial_number,
                'scanned_serial_number' => $task->scanned_serial_number,
                'qty_pass' => $task->qty_pass,
                'qty_fail' => $task->qty_fail,
                'condition' => $task->condition,
                'reason' => $task->reason,
                'checklist' => $checklist,
                'attachments' => $task->attachments ?? [],
            ];

            // Split based on whether item exists in SAP serial/batch list OR already has a serial number
            if (!empty($task->serial_number) || in_array($task->item_code, $serialManagedItemCodes)) {
                $items_strict[] = $item;
            } else {
                $items_manual[] = $item;
            }
        }

        $data['items_strict'] = $items_strict;
        $data['items_manual'] = $items_manual;

        return $data;
    }
}
