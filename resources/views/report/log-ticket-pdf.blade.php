<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Senarai Log Tiket</title>
    <style>
        @page {
            size: landscape;
            margin: 20px;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            margin: 0;
            padding: 15px;
            background-color: #f8f9fa;
        }

        .header {
            background-color: #4CAF50;
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

        th,
        td {
            border: 1px solid #ddd;
            padding: 6px;
            text-align: left;
        }

        th {
            background-color: #4CAF50;
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
        <h1>SENARAI LOG TIKET</h1>
        <p style="margin: 0; font-size: 9px;">Tarikh Dijana: {{ $generatedDate }}</p>
    </div>

    <div class="stats">
        <div class="stat-card">
            <div class="stat-label">Jumlah Tiket</div>
            <div class="stat-value">{{ $totalTickets }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Aduan Selesai</div>
            <div class="stat-value">{{ $resolvedTickets }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Peratusan</div>
            <div class="stat-value">{{ $resolvedPercentage }}%</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Belum Selesai</div>
            <div class="stat-value">{{ $totalTickets - $resolvedTickets }}</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 5%;">Bil</th>
                <th style="width: 10%;">No. Log Tiket</th>
                <th style="width: 12%;">Pengadu</th>
                <th style="width: 10%;">Bahagian</th>
                <th style="width: 12%;">Tarikh Tiket</th>
                <th style="width: 8%;">Status</th>
                <th style="width: 12%;">Kategori</th>
                <th style="width: 12%;">Sub Kategori</th>
                <th style="width: 12%;">Pegawai Tugasan</th>
            </tr>
        </thead>
        <tbody>
            @php
                $bil = 1;
                $bulan = [
                    1 => 'JANUARI', 2 => 'FEBRUARI', 3 => 'MAC', 4 => 'APRIL',
                    5 => 'MEI', 6 => 'JUN', 7 => 'JULAI', 8 => 'OGOS',
                    9 => 'SEPTEMBER', 10 => 'OKTOBER', 11 => 'NOVEMBER', 12 => 'DISEMBER'
                ];
            @endphp
            @foreach ($tickets as $ticket)
                @php
                    // Format Tarikh Tiket in Malay
                    $tarikhTiket = '-';
                    if ($ticket->created_at) {
                        $tarikhTiket = $ticket->created_at->format('d') . ' ' . $bulan[$ticket->created_at->month] . ' ' . $ticket->created_at->format('Y');
                    }
                @endphp
                <tr>
                    <td>{{ $bil++ }}</td>
                    <td>{{ $ticket->ticket_nolog }}</td>
                    <td>{{ $ticket->user ? $ticket->user->nama : '-' }}</td>
                    <td>{{ $ticket->user && $ticket->user->bahagian ? $ticket->user->bahagian->nama_bah : '-' }}</td>
                    <td>{{ $tarikhTiket }}</td>
                    <td>{{ $ticket->status ? $ticket->status->st_name : '-' }}</td>
                    <td>{{ $ticket->category ? $ticket->category->cat_name : '-' }}</td>
                    <td>{{ $ticket->subCategory ? $ticket->subCategory->subcat_name : '-' }}</td>
                    <td>{{ $ticket->officerNames ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p><strong>Sistem eHelp</strong> - Dokumen ini dijana secara automatik</p>
    </div>
</body>
</html>
