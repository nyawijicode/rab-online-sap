<?php

namespace App\Traits;

use App\Models\SystemHistory;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Auth;

trait HasSystemHistory
{
    public static function bootHasSystemHistory()
    {
        static::created(function ($model) {
            $model->createHistoryLog('Dibuat', null, null, 'Data baru dibuat');
        });

        static::updated(function ($model) {
            $ignore = ['updated_at', 'deleted_at'];
            foreach ($model->getDirty() as $key => $value) {
                if (in_array($key, $ignore)) continue;

                $old = $model->getOriginal($key);
                $new = $value;

                if ($old != $new) {
                    $oldValue = $old;
                    $newValue = $new;

                    if ($oldValue instanceof \BackedEnum) {
                        $oldValue = $oldValue->value;
                    } elseif (is_object($oldValue) || is_array($oldValue)) {
                        $oldValue = json_encode($oldValue);
                    }

                    if ($newValue instanceof \BackedEnum) {
                        $newValue = $newValue->value;
                    } elseif (is_object($newValue) || is_array($newValue)) {
                        $newValue = json_encode($newValue);
                    }

                    $model->createHistoryLog(
                        $key,
                        (string)$oldValue,
                        (string)$newValue,
                        ucfirst(str_replace('_', ' ', $key)) . ' berubah'
                    );
                }
            }
        });

        static::deleted(function ($model) {
            $model->createHistoryLog('Dihapus', null, null, 'Data dihapus');
        });
    }

    public function histories(): MorphMany
    {
        return $this->morphMany(SystemHistory::class, 'model');
    }

    /**
     * Helper manual untuk mencatat histori tanpa field spesifik.
     */
    public function logActivity($description, $user = null)
    {
        $this->histories()->create([
            'user_id' => $user ? $user->id : Auth::id(),
            'description' => $description,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function createHistoryLog($field, $old, $new, $description)
    {
        $this->histories()->create([
            'user_id' => Auth::id(),
            'field' => $field,
            'old_value' => $old,
            'new_value' => $new,
            'description' => $description,
        ]);
    }
}
