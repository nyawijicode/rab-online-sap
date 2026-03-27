<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Enums\StagingEnum;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use App\Models\ServicePhoto;

class Service extends Model
{
    use HasFactory, SoftDeletes;
    use \App\Traits\HasSystemHistory;

    protected $fillable = [
        'user_id',
        'id_paket',
        'jenis_servis',
        'nama_dinas',
        'kontak',
        'no_telepon',
        'kerusakan',
        'nama_barang',
        'noserial',
        'masih_garansi',
        'nomer_so',
        'staging',
        'keterangan_staging',
        'company'
    ];

    protected $casts = [
        'staging' => StagingEnum::class,
        // HAPUS casting boolean untuk masih_garansi
        // 'masih_garansi' => 'boolean',
    ];
    protected static function booted(): void
    {
        static::saving(function ($model) {
            if ($model->jenis_servis === 'inventaris') {
                $model->id_paket   = '-';
                $model->nama_dinas = '-';
                $model->kontak     = '-';
                $model->no_telepon = '-';
            }
        });
        static::creating(function (Service $model) {
            // SELALU set dari generator yg benar, jangan conditional blank()
            $model->nomer_so = self::nextNomorSO();
        });
    }

    /**
     * Generate nomor SO format: SRV/YYMMDD/NNNNN
     * - NNNNN naik terus (global), ikut data soft-deleted
     * - rollover ke 00001 ketika > 99999
     * - pakai transaksi + lockForUpdate agar tidak tabrakan
     */
    public static function nextNomorSO(): string
    {
        $prefix = 'SRV/' . now()->format('ymd');

        return DB::transaction(function () use ($prefix) {
            $max = self::withTrashed()
                ->whereRaw("nomer_so REGEXP '^SRV/[0-9]{6}/[0-9]{5}$'")
                ->lockForUpdate()
                ->selectRaw('MAX(CAST(RIGHT(nomer_so, 5) AS UNSIGNED)) AS max_suffix')
                ->value('max_suffix');

            $next = ((int) ($max ?? 0)) + 1;
            if ($next > 99999) $next = 1;

            return $prefix . '/' . str_pad($next, 5, '0', STR_PAD_LEFT);
        });
    }
    public static function peekNextNomorSO(): string
    {
        $prefix = 'SRV/' . now()->format('ymd');

        $max = self::withTrashed()
            ->whereRaw("nomer_so REGEXP '^SRV/[0-9]{6}/[0-9]{5}$'")
            ->selectRaw('MAX(CAST(RIGHT(nomer_so, 5) AS UNSIGNED)) AS max_suffix')
            ->value('max_suffix');

        $next = ((int) ($max ?? 0)) + 1;
        if ($next > 99999) $next = 1;

        return $prefix . '/' . str_pad($next, 5, '0', STR_PAD_LEFT);
    }

    public static function generateNomorSO(): string
    {
        // Prefix tetap pakai tanggal berjalan
        $prefix = 'SRV/' . now()->format('ymd');

        return DB::transaction(function () use ($prefix) {
            // Ambil record dengan suffix terbesar (ikut yang soft-deleted), lalu LOCK baris itu.
            $last = self::withTrashed()
                ->orderByRaw('CAST(RIGHT(nomer_so, 5) AS UNSIGNED) DESC')
                ->lockForUpdate()
                ->first();

            $lastNumber = $last?->nomer_so
                ? (int) substr($last->nomer_so, -5)
                : 0;

            // N + 1 (global), rollover jika > 99999
            $nextNumber = $lastNumber + 1;
            if ($nextNumber > 99999) {
                $nextNumber = 1;
            }

            $suffix = str_pad($nextNumber, 5, '0', STR_PAD_LEFT);

            return $prefix . '/' . $suffix;
        });
    }
    // Relationship dengan user
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Relationship dengan semua logs service
    public function serviceLogs(): HasMany
    {
        return $this->hasMany(ServiceLog::class)->orderBy('created_at', 'desc');
    }

    // Relationship dengan logs staging saja
    public function stagingLogs(): HasMany
    {
        return $this->hasMany(ServiceLog::class)
            ->where('field_changed', 'staging')
            ->orderBy('created_at', 'desc');
    }

    // Accessor untuk mendapatkan nilai string dari enum
    public function getStagingValueAttribute(): string
    {
        return $this->staging->value;
    }

    // Accessor untuk mendapatkan label dari enum
    public function getStagingLabelAttribute(): string
    {
        return $this->staging->label();
    }

    // Accessor untuk mendapatkan label garansi
    public function getMasihGaransiLabelAttribute(): string
    {
        return $this->masih_garansi === 'Y' ? 'Ya' : 'Tidak';
    }

    // Scope untuk filtering berdasarkan staging
    public function scopeWhereStaging($query, $staging)
    {
        if ($staging instanceof StagingEnum) {
            return $query->where('staging', $staging->value);
        }

        if (is_string($staging) && StagingEnum::tryFrom($staging)) {
            return $query->where('staging', $staging);
        }

        return $query;
    }

    // Scope untuk filtering berdasarkan garansi
    public function scopeWhereMasihGaransi($query, $value)
    {
        if ($value === true || $value === 'Y') {
            return $query->where('masih_garansi', 'Y');
        }

        if ($value === false || $value === 'T') {
            return $query->where('masih_garansi', 'T');
        }

        return $query;
    }

    public function items()
    {
        return $this->hasMany(ServiceItem::class, 'service_id');
    }
    public function pengajuanBiaya()
    {
        return $this->hasMany(PengajuanBiayaService::class, 'service_id');
    }
    public function photos(): HasMany
    {
        return $this->hasMany(ServicePhoto::class, 'service_id');
    }

    public function statuses(): HasMany
    {
        return $this->hasMany(ServiceStatus::class, 'service_id');
    }
}
