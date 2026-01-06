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
    </div>

    <div class="stats">
        <div class="stat-card">
            <div class="stat-label">Jumlah Pegawai</div>
            <div class="stat-value">{{ $totalOfficers }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Jumlah Tiket</div>
            <div class="stat-value">{{ $totalTickets }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Jumlah High</div>
            <div class="stat-value">{{ $totalHigh }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Jumlah Medium</div>
            <div class="stat-value">{{ $totalMedium }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Jumlah Low</div>
            <div class="stat-value">{{ $totalLow }}</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 5%;">Bil</th>
                <th style="width: 15%;">Pegawai Tugasan</th>
                <th style="width: 10%;">Tiket Diterima</th>
                <th style="width: 10%;">Tiket Selesai</th>
                <th style="width: 10%;">Tiket Dalam Tindakan</th>
                <th style="width: 10%;">Tiket Lewat SLA</th>
                <th style="width: 15%;">Tiket Selesai Dalam SLA</th>
                <th style="width: 8%;">High</th>
                <th style="width: 8%;">Medium</th>
                <th style="width: 9%;">Low</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data as $index => $row)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $row->pegawai_tugasan ?? '-' }}</td>
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
                <td colspan="10" style="text-align: center;">Tiada data</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <p><strong>Sistem eHelp</strong> - Dokumen ini dijana secara automatik</p>
    </div>
</body>
</html>
