<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Casts\Attribute; // ✅ perbaikan import
use App\Models\Pengajuan;

class RequestTeknisi extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'no_request',
        'company_code', // NEW: simpan kode perusahaan
        'id_paket',
        'nama_dinas',
        'nama_kontak',
        'no_telepon',
        'jenis_pekerjaan',
        'divisi',
        'cabang',
        'tanggal_pelaksanaan',
        'tanggal_penjadwalan',
        'teknisi_id', // legacy - tetap dipertahankan
        'status',
        'keterangan',
        'user_id',
        'pengajuan_dinas_id',
        'no_rab',
        'final_status',
        'finalized_at',
        'finalized_by',
        'rejection_reason',
        'closing',
    ];

    protected $casts = [
        'tanggal_pelaksanaan' => 'date',     // = Request
        'tanggal_penjadwalan' => 'date',     // = Jadwal
        'created_at'          => 'datetime',
        'updated_at'          => 'datetime',
        'finalized_at'        => 'datetime',
        'closing'             => 'boolean',
    ];

    protected static function booted()
    {
        static::creating(function (self $model) {
            $date   = now()->format('ymd');          // tetap dipakai di prefix
            $prefix = "RT/{$date}/";

            // Ambil max suffix 5 digit dari SEMUA no_request (termasuk yang soft deleted)
            $lastNumber = self::withTrashed()
                ->selectRaw("MAX(CAST(SUBSTRING_INDEX(no_request, '/', -1) AS UNSIGNED)) as max_seq")
                ->value('max_seq');

            $next = str_pad(((int) $lastNumber) + 1, 5, '0', STR_PAD_LEFT);

            $model->no_request = $prefix . $next;

            if (blank($model->status)) {
                $model->status = 'request';
            }
            if (blank($model->final_status)) {
                $model->final_status = 'pending';
            }
            if (blank($model->company_code)) {
                $model->company_code = 'sap'; // Default SAP jika tidak terpilih
            }
        });

        static::saving(function ($model) {
            if (! $model->closing) {
                $model->id_paket   = $model->id_paket ?: 'Belum Closing';
            }
            $model->nama_dinas = $model->nama_dinas ?: 'Belum Closing';
        });
    }

    public function decided(): bool
    {
        return in_array($this->final_status, ['disetujui', 'ditolak'], true);
    }

    protected function tanggalPenjadwalan(): Attribute
    {
        return Attribute::make(
            set: function ($value) {
                if (blank($value)) {
                    return null;
                }

                // Jika sudah DateTime / Carbon, simpan apa adanya
                if ($value instanceof \DateTimeInterface) {
                    return $value;
                }

                $value = trim((string) $value);

                // d-m-Y → Y-m-d
                if (preg_match('/^\d{2}-\d{2}-\d{4}$/', $value)) {
                    return Carbon::createFromFormat('d-m-Y', $value)->format('Y-m-d');
                }

                // Y-m-d → Y-m-d (tetap)
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                    return $value;
                }

                // fallback: biarkan Carbon yang parse
                return Carbon::parse($value)->format('Y-m-d');
            }
        );
    }

    // Relasi ke Company via kode
    public function company(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Company::class, 'company_code', 'kode');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Legacy single teknisi (biar kode lama masih aman)
    public function teknisi(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teknisi_id');
    }

    // NEW: Many-to-many teknisi
    public function teknisis(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'request_teknisi_user', 'request_teknisi_id', 'user_id')
            ->withTimestamps();
    }

    public function reports(): HasMany
    {
        return $this->hasMany(RequestTeknisiReport::class);
    }

    public function histories(): HasMany
    {
        return $this->hasMany(RequestTeknisiHistory::class);
    }

    public function pengajuan(): BelongsTo
    {
        return $this->belongsTo(Pengajuan::class, 'pengajuan_id');
    }

    /**
     * Link satu / banyak RequestTeknisi ke sebuah Pengajuan.
     *
     * $requestTeknisiId bisa berupa:
     * - int      → id tunggal
     * - string   → "18" atau '["18","19"]'
     * - array    → [18, 19]
     * - null     → diabaikan
     */
    public static function linkToPengajuan(int|string|array|null $requestTeknisiId, Pengajuan $pengajuan): void
    {
        if ($requestTeknisiId === null || $requestTeknisiId === '') {
            return;
        }

        // Normalisasi: kalau string JSON, decode dulu
        if (is_string($requestTeknisiId)) {
            $decoded = json_decode($requestTeknisiId, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $requestTeknisiId = $decoded;
            } else {
                // string biasa, jadikan array 1 elemen
                $requestTeknisiId = [$requestTeknisiId];
            }
        }

        // Normalisasi jadi array of int
        $ids = is_array($requestTeknisiId) ? $requestTeknisiId : [$requestTeknisiId];

        $ids = collect($ids)
            ->filter(fn($id) => $id !== null && $id !== '')
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        if (empty($ids)) {
            return;
        }

        // Ambil nomor RAB dari pengajuan (fallback beberapa field)
        $attrs = $pengajuan->getAttributes();
        $noRab = $attrs['no_rab'] ?? $attrs['nomor'] ?? $attrs['no_pengajuan'] ?? null;

        // Update semua RequestTeknisi yang dipilih
        self::whereIn('id', $ids)->update([
            'pengajuan_id' => $pengajuan->id,
            'no_rab'       => $noRab,
        ]);
    }

    /** Helper: apakah $userId termasuk teknisi yg ditugaskan (pivot) atau legacy teknisi_id */
    public function isAssignedToUser(int $userId): bool
    {
        if ((int) $this->teknisi_id === $userId) {
            return true; // legacy
        }

        // Cek pivot (hindari N+1, tapi ini dipakai pada record yang sudah dimuat)
        return $this->teknisis->pluck('id')->contains($userId);
    }
}
