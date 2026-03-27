<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QcTaskCriteria extends Model
{
    protected $table = 'qc_task_criteria';

    protected $fillable = [
        'qc_task_id',
        'qc_criteria_id',
        'is_checked',
    ];

    protected $casts = [
        'is_checked' => 'boolean',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(QcTask::class, 'qc_task_id');
    }

    public function criteria(): BelongsTo
    {
        return $this->belongsTo(QcCriteria::class, 'qc_criteria_id');
    }
}
