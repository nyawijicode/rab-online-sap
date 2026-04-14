<div class="bg-white">
    <style>
        .bw-table {
            width: 100%;
            border-collapse: collapse;
            font-family: ui-sans-serif, system-ui, sans-serif;
            border: 2px solid black;
        }

        .bw-table th {
            border: 2px solid black;
            padding: 10px;
            text-align: left;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            color: black;
            background-color: white;
        }

        .bw-table td {
            padding: 12px;
            vertical-align: top;
            border: 2px solid black;
            color: black;
        }

        .title-bold {
            font-weight: 800;
            font-size: 14px;
            display: block;
            margin-bottom: 4px;
            color: black;
        }

        .text-normal {
            font-size: 12px;
            color: black;
            line-height: 1.4;
        }

        .pic-info {
            margin-top: 10px;
            font-size: 12px;
            color: black;
            font-weight: 700;
        }

        .qty-text {
            font-size: 24px;
            font-weight: 900;
            text-align: center;
            color: black;
        }

        .note-container {
            margin-top: -2px;
            /* overlap border */
            border: 2px solid black;
            padding: 12px;
            background-color: white;
        }

        .note-title {
            font-size: 11px;
            font-weight: 800;
            color: black;
            text-transform: uppercase;
            margin-bottom: 4px;
            display: block;
        }

        .note-body {
            font-size: 12px;
            font-weight: 700;
            color: black;
            line-height: 1.5;
        }
    </style>

    <table class="bw-table">
        <thead>
            <tr>
                <th style="width: 25%;">Pick Up</th>
                <th style="width: 25%;">Kirim Ke</th>
                <th style="width: 40%;">Keterangan</th>
                <th style="width: 10%; text-align: center;">Qty</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    <span class="title-bold">{{ strtoupper($record->tagihan_ke ?: '-') }}</span>
                    <div class="title-bold">{{ strtoupper($record->alamat_ambil ?: '-') }}</div>
                    <div class="title-bold">
                        PIC: {{ strtoupper($record->cabang_pic_name ?: ($record->vendor_pic_name ?: '-')) }}<br>
                        {{ $record->cabang_pic_phone ?: ($record->vendor_pic_phone ?: '-') }}
                    </div>
                </td>
                <td>
                    <span
                        class="title-bold">{{ strtoupper($record->customer_name ?: ($record->vendor_name ?: '-')) }}</span>
                    <div class="title-bold">{{ strtoupper($record->tujuan_pengiriman ?: '-') }}</div>
                    <div class="title-bold">
                        PIC: {{ strtoupper($record->penerima_pic_name ?: ($record->vendor_pic_name ?: '-')) }}<br>
                        {{ $record->penerima_pic_phone ?: ($record->vendor_pic_phone ?: '-') }}
                    </div>
                </td>
                <td style="text-align: center; vertical-align: middle;">
                    @php
                        if ($record->id_paket) {
                            $idPaket = $record->id_paket;
                        } elseif ($record->package_id) {
                            $idPaket = $record->package_id;
                        } elseif ($record->no_resi) {
                            $idPaket = $record->no_resi;
                        } else {
                            $idPaket = $record->po_number;
                        }
                    @endphp
                    <span class="title-bold" style="font-size: 16px;">{{ strtoupper($idPaket ?: '-') }}</span>
                </td>
                <td style="text-align: center; vertical-align: middle;">
                    @php
                        $groupedItems = $record->items()
                            ->selectRaw('unit, sum(pickup_quantity) as total')
                            ->groupBy('unit')
                            ->get();
                    @endphp
                    @forelse($groupedItems as $item)
                        <div class="qty-text">{{ (int) $item->total }}</div>
                        <div style="font-size: 11px; font-weight: 900; margin-bottom: 4px;">{{ strtoupper($item->unit ?: 'KOLI') }}</div>
                    @empty
                        <div class="qty-text">0</div>
                        <div style="font-size: 11px; font-weight: 900;">KOLI</div>
                    @endforelse
                </td>
            </tr>
        </tbody>
    </table>

    <div class="note-container">
        <span class="note-title">Note</span>
        <div class="note-body">
            @php
                $tglKirim = $record->jangka_waktu_pelaksanaan ?: $record->pickup_date;
                $hari = '';
                $tglFormat = '';
                if ($tglKirim) {
                    try {
                        $carbonDate = \Illuminate\Support\Carbon::parse($tglKirim)->locale('id');
                        $hari = strtoupper($carbonDate->translatedFormat('l'));
                        $tglFormat = $carbonDate->format('d/m/Y');
                    } catch (\Exception $e) {
                    }
                }
            @endphp
            DI FOTO PENERIMA BARANG DAN LAMPIRAN KEMBALI DI TTD STEMPEL
            @if($tglKirim)
                <span>( PAKET DI KIRIM KE PENERIMA HARI {{ $hari }} TGL {{ $tglFormat }} )</span>
            @endif
            @if($record->notes)
                <div style="margin-top: 8px; font-style: italic; font-weight: 600;">
                    "{{ strtoupper($record->notes) }}"
                </div>
            @endif
        </div>
    </div>
</div>