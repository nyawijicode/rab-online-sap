<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

use Illuminate\Database\Eloquent\SoftDeletes;

class QcTask extends Model
{
    use SoftDeletes;

    protected $table = 'qc_tasks';

    protected $fillable = [
        'doc_entry',
        'qc_no',
        'task_no',
        'base_line_id',
        'item_code',
        'item_name',
        'qty',
        'serial_number',
        'technician_id',
        'coordinator_id',
        'assigned_at',
        'completed_at',
        'status',
        'qty_pass',
        'qty_fail',
        'condition',
        'reason',
        'scanned_serial_number',
        'attachments',
        'is_printed',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'completed_at' => 'datetime',
        'qty' => 'decimal:4',
        'qty_pass' => 'decimal:4',
        'qty_fail' => 'decimal:4',
        'attachments' => 'array',
        'is_printed' => 'boolean',
    ];

    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    public function coordinator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coordinator_id');
    }

    public function taskCriteria(): HasMany
    {
        return $this->hasMany(QcTaskCriteria::class, 'qc_task_id');
    }

    /**
     * Get other tasks in the same QC group.
     */
    public function groupedTasks(): HasMany
    {
        return $this->hasMany(QcTask::class, 'qc_no', 'qc_no')
            ->where('technician_id', $this->technician_id);
    }
}
