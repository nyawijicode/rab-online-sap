<?php

namespace App\Models;

use App\Enums\RequestMarcommKebutuhanEnum;
use App\Enums\RequestMarcommStatusEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequestMarcomm extends Model
{
    use HasFactory;
    use \App\Traits\HasSystemHistory;
    use SoftDeletes;

    protected $table = 'request_marcomms';

    protected $fillable = [
        'no_request',
        'user_id',
        'nama_pemohon',
        'jabatan',
        'kantor_cabang',
        'nama_atasan',
        'nomor_kantor',
        'email',
        'kebutuhan',
        'quantity',
        'foto',
        'tanggal_respon',
        'status',
        'tanggal_terkirim',
        'pengajuan_id',
        'companies_id',
    ];

    protected $casts = [
        'kebutuhan'        => 'array', // array dari enum value
        'foto'             => 'array',
        'tanggal_respon'   => 'datetime',
        'tanggal_terkirim' => 'datetime',
        'status'           => RequestMarcommStatusEnum::class,
    ];

    /**
     * Generator nomor request
     * Contoh hasil: MRC-2025-00001, MRC-2025-00002, dst.
     * Menggunakan withTrashed() agar record soft delete tetap dihitung.
     */
    public static function generateNoRequest(): string
    {
        $prefix = 'RM/' . now()->format('Y') . '/';

        // cari record terakhir (termasuk yg soft delete) dengan prefix ini
        $last = static::withTrashed()
            ->where('no_request', 'like', $prefix . '%')
            ->orderByDesc('no_request')
            ->first();

        $nextNumber = 1;

        if ($last && $last->no_request) {
            // ambil angka di belakang prefix lalu +1
            $lastNumeric = (int) substr($last->no_request, strlen($prefix));
            $nextNumber  = $lastNumeric + 1;
        }

        return $prefix . str_pad((string) $nextNumber, 5, '0', STR_PAD_LEFT);
    }

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            // kalau masih kosong, auto-generate
            if (blank($model->no_request)) {
                $model->no_request = static::generateNoRequest();
            }
        });
        // ✅ Logic tanggal_respon & tanggal_terkirim
        static::saving(function (self $model) {
            // Kalau status berubah
            if ($model->isDirty('status')) {

                // isi tanggal_respon hanya sekali (pertama kali status berubah)
                if (blank($model->tanggal_respon)) {
                    $model->tanggal_respon = now();
                }

                // ambil nilai status (string)
                $statusValue = $model->status instanceof RequestMarcommStatusEnum
                    ? $model->status->value
                    : $model->status;

                // kalau di-set ke 'selesai' dan tanggal_terkirim masih kosong → isi
                if ($statusValue === RequestMarcommStatusEnum::SELESAI->value && blank($model->tanggal_terkirim)) {
                    $model->tanggal_terkirim = now();
                }
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // helper: ambil label kebutuhan sebagai array
    public function kebutuhanLabels(): array
    {
        if (! is_array($this->kebutuhan)) {
            return [];
        }

        $labels = [];
        foreach ($this->kebutuhan as $value) {
            $enum = RequestMarcommKebutuhanEnum::tryFrom($value);
            if ($enum) {
                $labels[] = $enum->label();
            }
        }

        return $labels;
    }
    public function pengajuan()
    {
        return $this->belongsTo(\App\Models\Pengajuan::class, 'pengajuan_id');
    }

    public function pengajuanMarcommKebutuhans()
    {
        return $this->hasMany(
            \App\Models\PengajuanMarcommKebutuhan::class,
            'request_marcomm_id'
        );
    }
    /**
     * Link satu / banyak RequestMarcomm ke sebuah Pengajuan.
     *
     * $requestMarcommId bisa:
     * - int
     * - string "10" / '["10","11"]'
     * - array [10,11]
     * - null → diabaikan
     */
    public static function linkToPengajuan(int|string|array|null $requestMarcommId, Pengajuan $pengajuan): void
    {
        if ($requestMarcommId === null || $requestMarcommId === '') {
            return;
        }

        // kalau string JSON → decode
        if (is_string($requestMarcommId)) {
            $decoded = json_decode($requestMarcommId, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $requestMarcommId = $decoded;
            } else {
                $requestMarcommId = [$requestMarcommId];
            }
        }

        // normalisasi: array of int unik
        $ids = is_array($requestMarcommId) ? $requestMarcommId : [$requestMarcommId];

        $ids = collect($ids)
            ->filter(fn($id) => $id !== null && $id !== '')
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        if (empty($ids)) {
            return;
        }

        // ==== DETACH request yang sebelumnya terhubung ke pengajuan ini,
        // tapi sekarang tidak dipilih lagi ====
        $currentIds = self::where('pengajuan_id', $pengajuan->id)->pluck('id')->all();
        $toDetach   = array_diff($currentIds, $ids);

        if (! empty($toDetach)) {
            self::whereIn('id', $toDetach)->update([
                'pengajuan_id' => null,
            ]);
        }

        // ==== ATTACH semua id yang dipilih ke pengajuan ini ====
        self::whereIn('id', $ids)->update([
            'pengajuan_id' => $pengajuan->id,
        ]);

        // ==== OPSIONAL: isi kolom request_marcomm_id di pengajuan_marcomm_kebutuhans
        // dipasang id pertama saja (karena satu baris detail cuma punya 1 kolom id)
        $firstId = $ids[0] ?? null;

        if ($firstId) {
            PengajuanMarcommKebutuhan::where('pengajuan_id', $pengajuan->id)
                ->update(['request_marcomm_id' => $firstId]);
        }
    }
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'companies_id');
    }
}
