<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>QC Labels - {{ count($labels) }} Units</title>
    <style>
        @page {
            size: 100mm 62mm;
            margin: 0;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            width: 100mm;
            height: 62mm;
            background: white;
            -webkit-print-color-adjust: exact;
        }

        .label-page {
            width: 100mm;
            height: 62mm;
            padding: 3mm 4mm;
            display: flex;
            flex-direction: column;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 1.5pt solid #000;
            padding-bottom: 2mm;
            margin-bottom: 2mm;
        }

        .qc-text {
            font-size: 32pt;
            font-weight: bold;
            line-height: 0.8;
            display: flex;
            align-items: center;
            justify-content: center;
            /* Center horizontally if needed, though mostly for vertical alignment match */
            height: 12mm;
            /* Match QR height */
            padding-top: 5.5mm;
            /* Visual adjustment to center vertically */
        }

        /* .sub-text removed as it is moving to info-section */

        .info-section {
            text-align: right;
            font-size: 9pt;
        }

        .info-row {
            display: flex;
            justify-content: flex-end;
            gap: 1.5mm;
            margin-bottom: 1mm;
        }

        .info-label {
            font-weight: bold;
        }

        .info-value {
            border-bottom: 0.8pt solid #000;
            min-width: 22mm;
            max-width: 22mm;
            word-wrap: break-word;
            overflow-wrap: break-word;
            font-size: 6.5pt;
            line-height: 1.2;
        }

        .whatsapp-section {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 2mm;
            margin: 1.5mm 0;
        }

        .qr-container {
            width: 15mm;
            height: 15mm;
            flex-shrink: 0;
        }

        .qr-container svg {
            width: 100%;
            height: 100%;
        }

        .qr-label {
            font-size: 5.5pt;
            font-weight: bold;
            writing-mode: vertical-rl;
            text-orientation: mixed;
        }

        .qr-description {
            font-size: 5.5pt;
            font-weight: bold;
            writing-mode: horizontal-tb;
            text-orientation: mixed;
        }

        .checklist-title {
            font-weight: bold;
            font-size: 9pt;
            margin-bottom: 1.5mm;
            padding-bottom: 1mm;
            border-bottom: 1.2pt solid #000;
        }

        .checklist-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            /* Restore 5 columns */
            gap: 1mm;
            padding: 1mm;
            border-bottom: 1.5pt solid #000;
        }

        .check-item {
            display: flex;
            align-items: center;
            font-size: 6pt;
            white-space: normal;
            word-wrap: break-word;
            overflow-wrap: break-word;
            line-height: 1.1;
        }

        .check-box {
            width: 3mm;
            height: 3mm;
            border: 0.6pt solid #000;
            margin-right: 1.2mm;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 6pt;
            flex-shrink: 0;
        }

        .footer {
            display: flex;
            flex-direction: column;
            align-items: center;
            border-top: 1.5pt solid #000;
            padding-top: 2mm;
        }

        .barcode-container {
            height: 12mm;
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 0mm;
        }

        .barcode-container svg {
            height: 100%;
            width: auto;
            max-width: 12mm;
            /* Square for QR */
        }



        @media print {
            .no-print {
                display: none;
            }

            body,
            .label-page {
                margin: 0;
                box-shadow: none;
            }
        }
    </style>
</head>

<body onload="window.print()">
    @foreach($labels as $label)
        @php
            $task = $label['task'];
            $checklist = $label['checklist'];
            $barcode = $label['barcode'];
            $qr_whatsapp = $label['qr_whatsapp'];
        @endphp
        <div class="label-page">
            <div class="header">
                <div>
                    <div class="qc-text">QC</div>
                </div>
                <div class="whatsapp-section">
                    <div class="qr-container">
                        {!! $qr_whatsapp !!}
                        <div class="qr-description">
                            <span>&#128222;</span> 08112945094
                        </div>
                    </div>
                    <div class="qr-label">Hotline Service</div>
                </div>

                <div class="info-section">
                    <div class="info-row">
                        <span class="info-label">Date.</span>
                        <span class="info-value">{{ $task->completed_at?->format('d/m/Y') ?? date('d/m/Y') }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Checker.</span>
                        <span class="info-value">{{ $task->technician?->name }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Task No.</span>
                        <span class="info-value">{{ $task->task_no }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-value"
                            style="font-weight: bold; font-size: 8pt; border: none; text-align: right; width: 100%; max-width: none;">PUSAT
                            - SRG</span>
                    </div>
                </div>
            </div>

            <div class="checklist-title">Checklist.</div>
            <div class="content-body"
                style="display: flex; align-items: stretch; margin-top: 0.5mm; border-bottom: 2pt solid #000; height: 32mm;">
                <div class="checklist-grid" style="flex: 0 0 70%; border-bottom: none; margin-top: 0; padding-right: 2mm;">
                    @foreach($criteria as $c)
                        <div class="check-item" style="margin-bottom: 0;">
                            <div class="check-box"
                                style="width: 3mm; height: 3mm; font-size: 7pt; line-height: 3mm; margin-right: 1mm;">
                                @if(isset($checklist[$c->id]) && $checklist[$c->id])
                                    &#10003;
                                @endif
                            </div>
                            <span style="font-size: 6pt;">{{ $c->name }}</span>
                        </div>
                    @endforeach
                </div>
                <div class="sn-section"
                    style="flex: 0 0 30%; display: flex; flex-direction: column; align-items: center; justify-content: center; border-left: 2pt solid #000; padding: 0;">
                    <div class="qr-container"
                        style="width: 100%; height: auto; display: flex; justify-content: center; margin-bottom: 1mm;">
                        <!-- Resize QR to fit compact space -->
                        <div style="width: 15mm; height: 15mm;">
                            {!! $barcode !!}
                            <div class="qr-description">
                                {{ $task->scanned_serial_number }}
                            </div>
                        </div>
                        <div class="qr-label">Scan SN</div>
                    </div>
                </div>
            </div>


        </div>
    @endforeach

    <div class="no-print" style="position:fixed; top:10px; right:10px;">
        <button onclick="window.print()"
            style="padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer;">Print
            Again</button>
        <!-- Tombol Kembali ke Daftar -->
        <button onclick="window.location.href='{{ url('/qc/qc-task-completeds') }}'"
            style="padding: 10px 20px; background: #28a745; color: white; border: none; border-radius: 5px; cursor: pointer; margin-top: 10px;">
            Kembali ke Daftar
        </button>
    </div>
</body>

</html>