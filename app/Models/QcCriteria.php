<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QcCriteria extends Model
{
    protected $table = 'qc_criteria';

    protected $fillable = [
        'name',
    ];

    public function taskCriteria(): HasMany
    {
        return $this->hasMany(QcTaskCriteria::class, 'qc_criteria_id');
    }
}
