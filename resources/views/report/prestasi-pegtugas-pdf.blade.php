<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Laporan Prestasi Pegawai Tugasan</title>
    <style>
        @page { margin: 20px; }
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            margin: 0;
            padding: 15px;
            background-color: #f8f9fa;
        }
        .header {
            background-color: #007bff;
            color: white;
            padding: 15px;
            text-align: center;
            border-radius: 8px;
            margin-bottom: 15px;
        }
        h1 {
            font-size: 16px;
            margin: 0 0 8px 0;
            font-weight: bold;
        }
        .info {
            background-color: white;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 15px;
            border: 1px solid #dee2e6;
            text-align: center;
            font-size: 9px;
        }
        .stats {
            display: flex;
            justify-content: space-around;
            margin-bottom: 15px;
            gap: 10px;
        }
        .stat-card {
            background: white;
            border-radius: 6px;
            padding: 10px;
            flex: 1;
            text-align: center;
            border: 2px solid #e9ecef;
        }
        .stat-label {
            font-size: 8px;
            color: #6c757d;
            text-transform: uppercase;
            font-weight: bold;
        }
        .stat-value {
            font-size: 18px;
            font-weight: bold;
            color: #495057;
            margin-top: 3px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            background: white;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 6px;
            text-align: left;
        }
        th {
            background-color: #007bff;
            color: white;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .footer {
            margin-top: 20px;
            padding-top: 12px;
            border-top: 1px solid #dee2e6;
            text-align: center;
            font-size: 8px;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>LAPORAN PRESTASI PEGAWAI TUGASAN</h1>
        <p style="margin: 0; font-size: 9px;">Tarikh Dijana: {{ $generatedDate }}</p>
        @if($filterBulanName || $filterTahun)
        <p style="margin: 5px 0 0 0; font-size: 9px; font-weight: bold;">
            Tempoh:
            @if($filterBulanName) {{ $filterBulanName }} @endif
            @if($filterTahun) {{ $filterTahun }} @endif
        </p>
        @endif
    </div>

    <div class="stats">
        <div class="stat-card">
            <div class="stat-label">Jumlah Pegawai</div>
            <div class="stat-value">{{ $totalOfficers }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Tiket Diterima</div>
            <div class="stat-value">{{ $totalTickets }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Tiket Selesai</div>
            <div class="stat-value">{{ $totalTicketSelesai }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Dalam Tindakan</div>
            <div class="stat-value">{{ $totalTicketDalamTindakan }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Lewat SLA</div>
            <div class="stat-value">{{ $totalTicketLewatSla }}</div>
        </div>
    </div>

    <div class="stats">
        <div class="stat-card">
            <div class="stat-label">Selesai Dalam SLA</div>
            <div class="stat-value">{{ $totalTicketSelesaiDalamSla }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">High Priority</div>
            <div class="stat-value">{{ $totalHigh }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Medium Priority</div>
            <div class="stat-value">{{ $totalMedium }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Low Priority</div>
            <div class="stat-value">{{ $totalLow }}</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 4%;">Bil</th>
                <th style="width: 12%;">Pegawai Tugasan</th>
                <th style="width: 7%;">Bulan</th>
                <th style="width: 5%;">Tahun</th>
                <th style="width: 8%;">Tiket Diterima</th>
                <th style="width: 8%;">Tiket Selesai</th>
                <th style="width: 10%;">Tiket Dalam Tindakan</th>
                <th style="width: 8%;">Tiket Lewat SLA</th>
                <th style="width: 12%;">Tiket Selesai Dalam SLA</th>
                <th style="width: 7%;">High</th>
                <th style="width: 7%;">Medium</th>
                <th style="width: 7%;">Low</th>
            </tr>
        </thead>
        <tbody>
            @php
                $bulanNames = [
                    1 => 'Januari', 2 => 'Februari', 3 => 'Mac', 4 => 'April',
                    5 => 'Mei', 6 => 'Jun', 7 => 'Julai', 8 => 'Ogos',
                    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Disember'
                ];
            @endphp
            @forelse($data as $index => $row)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $row->pegawai_tugasan ?? '-' }}</td>
                <td style="text-align: center;">{{ $bulanNames[$row->bulan] ?? $row->bulan ?? '-' }}</td>
                <td style="text-align: center;">{{ $row->tahun ?? '-' }}</td>
                <td style="text-align: center;">{{ $row->tiket_diterima ?? 0 }}</td>
                <td style="text-align: center;">{{ $row->tiket_selesai ?? 0 }}</td>
                <td style="text-align: center;">{{ $row->tiket_dalam_tindakan ?? 0 }}</td>
                <td style="text-align: center;">{{ $row->tiket_lewat_sla ?? 0 }}</td>
                <td style="text-align: center;">{{ $row->tiket_selesai_dalam_sla ?? 0 }}</td>
                <td style="text-align: center;">{{ $row->high ?? 0 }}</td>
                <td style="text-align: center;">{{ $row->medium ?? 0 }}</td>
                <td style="text-align: center;">{{ $row->low ?? 0 }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="12" style="text-align: center;">Tiada data</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <p><strong>Sistem eHelp</strong> - Dokumen ini dijana secara automatik</p>
    </div>
</body>
</html>
