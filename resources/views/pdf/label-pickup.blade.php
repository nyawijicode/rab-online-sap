<!DOCTYPE html>
<html>
<head>
    <title>Label Pengiriman - {{ $pickup->id }}</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11pt;
            margin: 0;
            padding: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        td, th {
            border: 2px solid black;
            padding: 8px;
            vertical-align: top;
            word-wrap: break-word;
        }
        th {
            text-align: center;
            font-weight: bold;
            font-size: 10pt;
            background-color: transparent;
        }
        .text-center {
            text-align: center;
        }
        .text-bold {
            font-weight: bold;
        }
        .content-cell {
            height: 160px;
        }
    </style>
</head>
<body>

    <table>
        <tr>
            <th style="width: 32%">pick up :</th>
            <th style="width: 38%">kirim ke :</th>
            <th style="width: 20%">keterangan</th>
            <th style="width: 10%">qty</th>
        </tr>
        <tr>
            <td class="content-cell text-bold">
                {{ strtoupper($pickup->vendor_name) }}<br>
                {!! nl2br(e(strtoupper($pickup->vendor_address))) !!}<br><br>
                PIC : {{ strtoupper($pickup->vendor_pic_name) }} ({{ $pickup->vendor_pic_phone }})
            </td>
            <td class="content-cell text-bold">
                {{ strtoupper($pickup->tujuan_pengiriman) }}<br>
                {!! nl2br(e(strtoupper($pickup->alamat_dropship))) !!}<br>
                {{ strtoupper($pickup->kota) }}
            </td>
            <td class="content-cell text-center text-bold" style="vertical-align: middle;">
                {{ strtoupper($pickup->no_resi ?: ($pickup->package_id ?: $pickup->po_number)) }}
            </td>
            <td class="content-cell text-center text-bold" style="vertical-align: middle;">
                {{ (int) $pickup->items->sum('pickup_quantity') }} koli
            </td>
        </tr>
        <tr>
            <td colspan="4" class="text-center text-bold" style="padding: 12px; font-size: 10pt;">
                @php
                    // Ambil tanggal kirim dari jangka waktu pelaksanaan atau pickup date
                    $tglKirim = $pickup->jangka_waktu_pelaksanaan ?: $pickup->pickup_date;
                    
                    $hari = '';
                    $tglFormat = '';
                    if ($tglKirim) {
                        try {
                            $carbonDate = \Carbon\Carbon::parse($tglKirim)->locale('id');
                            $hari = strtoupper($carbonDate->translatedFormat('l'));
                            $tglFormat = $carbonDate->format('d/m/Y');
                        } catch (\Exception $e) {}
                    }
                @endphp
                NOTE : DI FOTO PENERIMA BARANG DAN LAMPIRAN KEMBALI DI TTD STEMPEL 
                @if($tglKirim)
                ( PAKET DI KIRIM KE PENERIMA HARI {{ $hari }} TGL {{ $tglFormat }} )
                @endif
                <br>
                {!! nl2br(e(strtoupper($pickup->notes))) !!}
            </td>
        </tr>
    </table>

</body>
</html>
