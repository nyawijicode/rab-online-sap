<?php

namespace App\Filament\Forms\Pengajuan;

use App\Models\RequestTeknisi;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class DinasFormSection
{
    public static function schema(): array
    {
        return [
            Section::make('Pengajuan RAB Perjalanan Dinas')
                ->schema([
                    Grid::make(4)->schema([
                        DatePicker::make('tgl_realisasi')
                            ->label('Tanggal Berangkat/ Realisasi')
                            ->dehydrated()
                            ->required()
                            ->default(now())
                            ->displayFormat('d F Y')
                            ->locale('id')
                            ->rules([
                                function (Get $get) {
                                    return function (string $attribute, $value, \Closure $fail) use ($get) {
                                        // Skip validasi jika mode urgent aktif
                                        if ($get('is_urgent')) {
                                            return;
                                        }

                                        // ✅ Skip validasi 36 jam saat EDIT (record sudah ada)
                                        $livewire = app(\Livewire\Livewire::class);
                                        $record = request()->route('record'); // tidak reliable

                                        // Cara yang lebih reliable di Filament:
                                        $recordId = $get('id'); // jika ada hidden field 'id'
                                        if ($recordId) {
                                            return;
                                        }

                                        $tanggal = $value;
                                        $jam = $get('jam') ?? '00:00';

                                        if (!$tanggal) {
                                            return;
                                        }

                                        $realisasi = \Carbon\Carbon::parse($tanggal . ' ' . $jam);
                                        $now = \Carbon\Carbon::now();
                                        $diffInHours = $now->diffInHours($realisasi, false);

                                        if ($diffInHours < 36) {
                                            $fail('Pengajuan harus dibuat minimal 36 jam sebelum tanggal keberangkatan. Jika ini adalah kasus dadakan, aktifkan dadakan dan lampirkan bukti.');
                                        }
                                    };
                                },
                            ])
                            ->reactive(),
                        DatePicker::make('tgl_pulang')
                            ->label('Tanggal Pulang')
                            ->dehydrated()
                            ->default(now())
                            ->displayFormat('d F Y')
                            ->locale('id'),
                        TextInput::make('jam')
                            ->label('Jam')
                            ->placeholder('Masukkan Jam (contoh: 13:30)')
                            ->mask('99:99')
                            ->rules(['required', 'date_format:H:i'])
                            ->default('08:30')
                            ->extraAttributes(['id' => 'jamPicker'])
                            ->reactive(),
                        TextInput::make('jml_personil')
                            ->label('Jumlah Personil')
                            ->default(1)
                            ->placeholder('Silahkan isi personil'),
                        // === MODE URGENT ===
                        Toggle::make('is_urgent')
                            ->label('Dadakan')
                            ->inline(false)
                            ->default(false)
                            ->onIcon('heroicon-s-exclamation-triangle')
                            ->offIcon('heroicon-s-clock')
                            ->onColor('danger')
                            ->offColor('gray')
                            ->reactive()
                            ->helperText('Aktifkan jika pengajuan ini bersifat mendadak (kurang dari 36 jam). Wajib melampirkan bukti perintah dari atasan.')
                            ->afterStateUpdated(function (Set $set, $state) {
                                if (!$state) {
                                    $set('urgent_proof_path', null);
                                }
                            }),

                        FileUpload::make('urgent_proof_path')
                            ->label('Bukti Perintah Dadakan (Surat/Screenshot WA/Email)')
                            ->disk('public')
                            ->directory('urgent-proofs')
                            ->image()
                            ->imageEditor(false)
                            ->acceptedFileTypes(['image/*', 'application/pdf'])
                            ->helperText('Upload gambar/PDF surat perintah kerja atau screenshot WhatsApp/email dari atasan sebagai bukti perintah urgent.')

                            ->visible(fn(Get $get) => $get('is_urgent') === true)

                            // 🔥 penting
                            ->dehydrated(fn(Get $get) => $get('is_urgent') === true)

                            // 🔥 gunakan ini, bukan closure required biasa
                            ->requiredIf('is_urgent', true)

                            ->validationMessages([
                                'required' => 'Bukti perintah urgent wajib dilampirkan untuk pengajuan dadakan.',
                            ]),
                        Toggle::make('use_car')
                            ->label('Request Mobil')
                            ->inline(false)
                            ->default(false)
                            ->onIcon('heroicon-s-check')
                            ->offIcon('heroicon-s-x-mark')
                            ->onColor('success')
                            ->offColor('danger')
                            ->reactive()
                            ->helperText('Digunakan untuk request penggunaan Mobil.'),
                        Toggle::make('use_pengiriman')
                            ->label('Pengiriman Barang/Gudang')
                            ->inline(false)
                            ->default(false)
                            ->onIcon('heroicon-s-check')
                            ->offIcon('heroicon-s-x-mark')
                            ->onColor('success')
                            ->offColor('danger')
                            ->reactive()
                            ->helperText('Bisa juga di gunakan untuk request penggunaan Mobil dan Sopir.'),
                        Toggle::make('menggunakan_teknisi')
                            ->label('Menggunakan Teknisi / Survey')
                            ->inline(false)
                            ->default(false)
                            ->onIcon('heroicon-s-check')
                            ->offIcon('heroicon-s-x-mark')
                            ->onColor('success')
                            ->offColor('danger')
                            ->reactive()
                            ->disabled(function () {
                                $user = Auth::user();

                                // role yang selalu bebas
                                if ($user->hasAnyRole(['superadmin', 'koordinator teknisi'])) {
                                    return false;
                                }

                                // ambil id user bawahan langsung
                                $subordinateIds = DB::table('user_statuses')
                                    ->where('atasan_id', $user->id)
                                    ->pluck('user_id');

                                // gabungkan id diri sendiri + bawahan
                                $scopeIds = $subordinateIds->push($user->id);

                                // boleh aktif jika ada minimal satu request di scope ini
                                $hasAnyRequest = RequestTeknisi::whereIn('user_id', $scopeIds)->exists();

                                return ! $hasAnyRequest;
                            })
                            ->helperText(function () {
                                $user = Auth::user();
                                if ($user->hasAnyRole(['superadmin', 'koordinator teknisi'])) {
                                    return 'Anda dapat memilih Request Teknisi mana pun.';
                                }

                                $subordinateIds = DB::table('user_statuses')
                                    ->where('atasan_id', $user->id)
                                    ->pluck('user_id');

                                $scopeIds = $subordinateIds->push($user->id);

                                $hasAnyRequest = RequestTeknisi::whereIn('user_id', $scopeIds)->exists();

                                return $hasAnyRequest
                                    ? 'Silakan pilih Request Teknisi.'
                                    : 'Belum ada Request Teknisi.';
                            }),

                        Select::make('request_teknisi_id') // nama tetap sama
                            ->label('Nomor Request Teknisi')
                            ->hidden(fn(Get $get) => ! $get('menggunakan_teknisi'))
                            ->dehydrated()
                            ->nullable()
                            ->searchable()
                            ->preload()
                            ->multiple() // ⬅️ ini yang bikin bisa pilih banyak
                            ->options(function () {
                                $user = Auth::user();

                                $base = RequestTeknisi::query()
                                    ->with(['user:id,name'])
                                    ->select(['id', 'no_request', 'user_id', 'jenis_pekerjaan'])
                                    ->orderByDesc('id');

                                if (! $user->hasAnyRole(['superadmin', 'koordinator teknisi'])) {
                                    $subordinateIds = DB::table('user_statuses')
                                        ->where('atasan_id', $user->id)
                                        ->pluck('user_id');

                                    $scopeIds = $subordinateIds->push($user->id);
                                    $base->whereIn('user_id', $scopeIds);
                                }

                                return $base->get()->mapWithKeys(function ($rt) {
                                    $nama = $rt->user?->name ?? '—';
                                    return [
                                        $rt->id => "{$rt->no_request} — {$nama} — {$rt->jenis_pekerjaan}",
                                    ];
                                })->toArray();
                            })
                            ->getSearchResultsUsing(function (string $search) {
                                $user = Auth::user();

                                $base = RequestTeknisi::query()
                                    ->join('users', 'users.id', '=', 'request_teknisis.user_id')
                                    ->where(function ($q) use ($search) {
                                        $q->where('request_teknisis.no_request', 'like', "%{$search}%")
                                            ->orWhere('users.name', 'like', "%{$search}%")
                                            ->orWhere('request_teknisis.jenis_pekerjaan', 'like', "%{$search}%");
                                    })
                                    ->select([
                                        'request_teknisis.id',
                                        'request_teknisis.no_request',
                                        'request_teknisis.jenis_pekerjaan',
                                        'users.name as user_name',
                                    ])
                                    ->orderByDesc('request_teknisis.id')
                                    ->limit(50);

                                if (! $user->hasAnyRole(['superadmin', 'koordinator teknisi'])) {
                                    $subordinateIds = DB::table('user_statuses')
                                        ->where('atasan_id', $user->id)
                                        ->pluck('user_id');

                                    $scopeIds = $subordinateIds->push($user->id);
                                    $base->whereIn('request_teknisis.user_id', $scopeIds);
                                }

                                return $base->get()->mapWithKeys(function ($row) {
                                    return [
                                        $row->id => "{$row->no_request} — {$row->user_name} — {$row->jenis_pekerjaan}",
                                    ];
                                })->toArray();
                            })
                            ->getOptionLabelUsing(function ($value) {
                                if (blank($value)) {
                                    return null;
                                }

                                $ids = is_array($value) ? $value : [$value];

                                $items = RequestTeknisi::query()
                                    ->with('user:id,name')
                                    ->select('id', 'no_request', 'user_id', 'jenis_pekerjaan')
                                    ->whereIn('id', $ids)
                                    ->get();

                                return $items->map(function ($rt) {
                                    return "{$rt->no_request} — " . ($rt->user->name ?? '—') . " — {$rt->jenis_pekerjaan}";
                                })->join(', ');
                            })
                            ->afterStateUpdated(function ($state, Set $set) {
                                // ambil dinas dari request pertama saja (kalau ada)
                                if (is_array($state) && count($state) > 0) {
                                    $firstId = $state[0];
                                    $rt = RequestTeknisi::find($firstId);
                                    $set('request_teknisi_nama_dinas', $rt?->nama_dinas);
                                } else {
                                    $set('request_teknisi_nama_dinas', null);
                                }
                            }),

                        TextInput::make('request_teknisi_nama_dinas')
                            ->label('Nama Dinas (Request Teknisi)')
                            ->hidden(fn(Get $get) => !$get('menggunakan_teknisi'))
                            ->dehydrated()
                            ->nullable(),
                    ]),
                    Repeater::make('pengajuan_dinas')
                        ->label('Form RAB Perjalanan Dinas')
                        ->relationship('pengajuan_dinas')
                        ->schema([
                            Select::make('deskripsi')
                                ->label('Deskripsi')
                                ->options([
                                    'Transportasi' => 'Transportasi',
                                    'Makan' => 'Makan',
                                    'Lain-lain' => 'Lain-lain',
                                ])
                                ->columnSpanFull()
                                ->required(),

                            Textarea::make('keterangan')
                                ->label('Keterangan')
                                ->placeholder('Contoh: Uang makan harian...')
                                ->nullable()
                                ->columnSpanFull(),

                            TextInput::make('pic')
                                ->label('PIC')
                                ->default(1)
                                ->placeholder('Contoh: Jumlah PIC 1,2,3...')
                                ->nullable(),

                            TextInput::make('jml_hari')
                                ->label('QTY/Hari')
                                ->numeric()
                                ->minValue(1)
                                ->default(1)
                                ->nullable()
                                ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                    $pic = (int) $get('pic');
                                    $jmlHari = (int) $state;
                                    $harga = (int) str_replace('.', '', $get('harga_satuan'));
                                    $set('subtotal', $jmlHari && $harga ? ($pic * $jmlHari) * $harga : null);
                                }),

                            TextInput::make('harga_satuan')
                                ->label('Harga')
                                ->placeholder('Contoh: 500000')
                                ->required()
                                ->default(0)
                                ->dehydrated()
                                ->prefix('Rp ')
                                ->extraAttributes(['class' => 'currency-input'])
                                ->afterStateHydrated(function (TextInput $component, $state) {
                                    // tampilkan dalam format ribuan
                                    $component->state($state ? number_format((int) $state, 0, ',', '.') : null);
                                })
                                ->dehydrateStateUsing(function ($state) {
                                    // sebelum simpan ke DB, hapus titik
                                    return $state ? (int) str_replace('.', '', $state) : 0;
                                })
                                ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                    $pic = (int) $get('pic');
                                    $jmlHari = (int) $get('jml_hari');
                                    $harga = (int) str_replace('.', '', $state);
                                    $set('subtotal', $pic && $jmlHari && $harga ? ($pic * $jmlHari) * $harga : null);
                                }),

                            TextInput::make('subtotal')
                                ->label('Subtotal')
                                ->disabled()
                                ->required()
                                ->dehydrated()
                                ->default(0)
                                ->prefix('Rp ')
                                ->extraAttributes(['class' => 'currency-input'])
                                ->afterStateHydrated(function (TextInput $component, $state) {
                                    // tampilkan dengan format ribuan
                                    $component->state($state ? number_format((int) $state, 0, ',', '.') : null);
                                })
                                ->dehydrateStateUsing(function ($state, Get $get) {
                                    // sebelum simpan ke DB, pastikan jadi angka murni
                                    $pic   = (int) $get('pic');
                                    $jml   = (int) $get('jml_hari');
                                    $harga = (int) str_replace('.', '', $get('harga_satuan'));
                                    return $pic && $jml && $harga ? ($pic * $jml) * $harga : 0;
                                })
                                ->columnSpanFull(),
                        ])
                        ->afterStateUpdated(function ($state, callable $set) {
                            // Hitung ulang total_biaya dari semua item
                            $total = collect($state)->sum(fn($item) => (int) ($item['subtotal'] ?? 0));
                            $set('total_biaya', $total);
                        })
                        ->columns(3)
                        ->addActionLabel('Tambah Perjalanan Dinas')
                        ->columnSpanFull()
                        ->defaultItems(1)
                        ->itemLabel('Detail Perjalanan Dinas'),

                    Toggle::make('closing')
                        ->label('Sudah closing?')
                        ->inline(false)
                        ->onIcon('heroicon-s-check')
                        ->offIcon('heroicon-s-x-mark')
                        ->onColor('success')
                        ->offColor('danger')
                        ->default(false)
                        ->live()
                        ->dehydrated()
                        ->helperText('Jika sudah closing, wajib menyalakan toggle (Ya).'),

                    // === Repeater anak: baca nilai closing parent ===
                    Repeater::make('dinasActivities')
                        ->label('Form Activity Perjalanan Dinas')
                        ->relationship('dinasActivities')
                        ->schema([
                            TextInput::make('no_activity')
                                ->label('No Activity')
                                ->required()
                                // Placeholder dinamis
                                ->placeholder(function (Get $get) {
                                    $isStrict = auth()->user()?->hasAnyRole(['sales']);
                                    return ($get('../../closing') || ! $isStrict)
                                        ? 'PKT-123456789/SAP/SSM'   // bebas
                                        : '2502-000001';            // format ####-######
                                })
                                // Help text dinamis
                                ->helperText(function (Get $get) {
                                    $isStrict = auth()->user()?->hasAnyRole(['sales']);
                                    return ($get('../../closing') || ! $isStrict)
                                        ? 'Masukan Nomor Paket/PO/SO/etc (contoh: PKT-123456789/SAP/SSM).'
                                        : 'Wajib format ####-###### dan harus ada di monitor4.premmiere.co.id (Chatbot).';
                                })
                                // Rules dinamis: ketat hanya untuk role sales/koordinator/spv saat BELUM closing
                                ->rules(function (Get $get) {
                                    $isStrict = auth()->user()?->hasAnyRole(['sales']);

                                    if ($get('../../closing') || ! $isStrict) {
                                        return ['string', 'min:3', 'max:128']; // bebas
                                    }

                                    // STRICT: format & exist di PostgreSQL
                                    return [
                                        'required',
                                        'regex:/^\d{4}-\d{6}$/',
                                        Rule::exists('monitor_sales_pgsql.activity', 'name'),
                                    ];
                                })
                                ->validationMessages([
                                    'regex'  => 'Format harus ####-###### (contoh: 2502-000001).',
                                    'exists' => 'No Activity tidak ditemukan di monitor4.premmiere.co.id (Chatbot).',
                                ])
                                // Auto-format hanya ketika STRICT (sales/koordinator/spv & belum closing)
                                ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                    $isStrict = auth()->user()?->hasAnyRole(['sales']);
                                    if ($get('../../closing') || ! $isStrict) {
                                        return;
                                    }
                                    $digits = preg_replace('/\D+/', '', (string) $state);
                                    if ($digits === '') {
                                        $set('no_activity', null);
                                        return;
                                    }
                                    $left  = substr($digits, 0, 4);
                                    $right = substr($digits, 4, 6);
                                    $set('no_activity', $right !== '' ? ($left . '-' . $right) : $left);
                                })
                                ->dehydrateStateUsing(fn($state) => trim((string) $state)),

                            Textarea::make('nama_dinas')
                                ->label('Nama Dinas')
                                ->required(),

                            Textarea::make('keterangan')
                                ->label('Keterangan')
                                ->placeholder('Visit/Follow Up/Dll')
                                ->required(),

                            // ========== FIELD KHUSUS SPV ==========
                            Textarea::make('pekerjaan')
                                ->label('Pekerjaan')
                                ->maxLength(65535)
                                ->visible(fn() => auth()->user()?->hasRole('spv'))
                                ->required(fn() => auth()->user()?->hasRole('spv')),

                            TextInput::make('nilai')
                                ->label('Nilai')
                                ->placeholder('Contoh: 500000')
                                ->prefix('Rp ')
                                ->extraAttributes(['class' => 'currency-input'])
                                ->dehydrated() // kirim ke server
                                // tampilkan 1.000.000 saat form dibuka
                                ->afterStateHydrated(function (TextInput $component, $state) {
                                    $component->state(($state !== null && $state !== '')
                                        ? number_format((int) $state, 0, ',', '.')
                                        : null);
                                })
                                // sebelum simpan/validasi: "1.234.567" -> 1234567
                                ->dehydrateStateUsing(function ($state) {
                                    $digits = preg_replace('/\D+/', '', (string) $state);
                                    return $digits === '' ? null : (int) $digits;
                                })
                                // muncul & wajib hanya untuk SPV
                                ->visible(fn() => auth()->user()?->hasRole('spv'))
                                ->required(fn() => auth()->user()?->hasRole('spv'))
                                // rules dinamis (tanpa closure-validator)
                                ->rules(
                                    fn() => auth()->user()?->hasRole('spv')
                                        ? ['required', 'integer', 'min:0']
                                        : ['nullable', 'integer', 'min:0']
                                ),

                            TextInput::make('target')
                                ->label('Target')
                                ->maxLength(255)
                                ->visible(fn() => auth()->user()?->hasRole('spv'))
                                ->required(fn() => auth()->user()?->hasRole('spv')),
                            // =======================================
                        ])
                        ->columns(3)
                        ->addActionLabel('Tambah Activity')
                        ->columnSpanFull()
                        ->defaultItems(1)
                        ->itemLabel('Detail Activity Perjalanan Dinas'),

                    Repeater::make('dinasPersonils')
                        ->relationship('dinasPersonils')
                        ->defaultItems(0) // jangan bikin item kosong
                        ->afterStateHydrated(function (Repeater $component, ?array $state) {
                            // Hanya saat CREATE (state masih kosong), seed 1 item: user pembuat
                            if (blank($state)) {
                                $component->state([[
                                    'nama_personil' => auth()->user()->name ?? 'Pengusul',
                                    'is_creator'    => true, // flag di state saja
                                ]]);
                            }
                        })
                        ->mutateRelationshipDataBeforeCreateUsing(function (array $data): array {
                            // Hardening: kalau masih kosong, isi nama pembuat
                            if (!filled($data['nama_personil'] ?? null)) {
                                $data['nama_personil'] = auth()->user()->name ?? 'Pengusul';
                            }
                            return $data;
                        })
                        ->schema([
                            Hidden::make('is_creator')
                                ->default(false)
                                ->dehydrated(false), // jangan ikut ke DB

                            TextInput::make('nama_personil')
                                ->label('Nama Personil')
                                ->required()
                                ->maxLength(250)
                                ->dehydrated() // tetap kirim ke server meski disabled
                                ->disabled(fn($get) => (bool) $get('is_creator')),
                        ])
                        ->addActionLabel('Tambah Personil')
                        ->itemLabel(fn(array $state) => !empty($state['is_creator'])
                            ? 'Anda (Pembuat Pengajuan)'
                            : 'Detail Personil Perjalanan Dinas')
                        ->columnSpanFull(),
                    TextInput::make('total_biaya')
                        ->label('Total Biaya')
                        ->disabled()
                        ->dehydrated()
                        ->default(0)
                        ->prefix('Rp ')
                        ->extraAttributes(['class' => 'currency-input'])
                        ->afterStateHydrated(function (TextInput $component, $state) {
                            $component->state($state ? number_format((int) $state, 0, ',', '.') : null);
                        })
                        ->dehydrateStateUsing(function ($state) {
                            return $state ? (int) str_replace('.', '', $state) : 0;
                        })
                        ->columnSpanFull(),
                    Toggle::make('lampiran_dinas')
                        ->label('Tambahkan Lampiran Perjalanan Dinas')
                        ->default(false)
                        ->onIcon('heroicon-s-check')
                        ->offIcon('heroicon-s-x-mark')
                        ->onColor('success')
                        ->offColor('danger')
                        ->reactive()
                        ->dehydrated(), // ⬅️ penting agar nilainya dikirim ke backend

                    Repeater::make('lampiranDinas')

                        ->label('Lampiran RAB Perjalanan Dinas')
                        ->relationship('lampiranDinas')
                        ->schema([
                            FileUpload::make('file_path')
                                ->label('File Lampiran (PDF/Gambar)')
                                ->disk('public')
                                ->directory('lampiran-dinas')
                                ->preserveFilenames()
                                ->acceptedFileTypes(['application/pdf', 'image/*'])
                                ->maxSize(10240)
                                ->required()
                                ->reactive()
                                ->afterStateUpdated(function (Set $set, $state) {
                                    if (is_array($state) && count($state) > 0) {
                                        $file = array_key_first($state);
                                        if ($file) {
                                            $filename = pathinfo($file, PATHINFO_BASENAME);
                                            $set('original_name', $filename);
                                        }
                                    }
                                }),

                            TextInput::make('original_name')
                                ->label('Nama Lampiran')
                                ->required()
                                ->maxLength(255),
                        ])
                        ->defaultItems(1)
                        ->visible(fn($get) => $get('lampiran_dinas') === true),
                    Forms\Components\Textarea::make('keterangan')
                        ->label('Keterangan')
                        ->placeholder(fn(Get $get) => $get('is_urgent') === true ? 'Jika ada alasan mendesak (dadakan), silakan tuliskan di sini...' : 'Jika ada keterangan tambahan, silahkan tuliskan di sini...')
                        ->required(fn(Get $get) => $get('is_urgent') === true)
                        ->rows(4)
                        ->maxLength(65535)
                        ->columnSpanFull(),
                ])
                ->visible(fn(Get $get) => $get('tipe_rab_id') == 2),
        ];
    }
}
