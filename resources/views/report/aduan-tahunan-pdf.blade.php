{{-- filepath: c:\laragon\www\ehelp\resources\views\report\aduan-tahunan-pdf.blade.php --}}
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Aduan Tahun {{ $year }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 9px;
            line-height: 1.4;
            padding: 10px;
        }

        .header {
            text-align: center;
            margin-bottom: 15px;
        }

        .header h1 {
            font-size: 16px;
            margin-bottom: 5px;
        }

        .header p {
            font-size: 10px;
            color: #666;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            font-size: 8px;
        }

        th, td {
            border: 1px solid #333;
            padding: 3px 2px;
            text-align: center;
        }

        th {
            background-color: #f0f0f0;
            font-weight: bold;
        }

        .month-header {
            background-color: #7367f0;
            color: white;
        }

        .total-header {
            background-color: #28c76f;
            color: white;
        }

        .category-col {
            text-align: left;
            min-width: 150px;
            background-color: #f9f9f9;
        }

        .total-row {
            background-color: #e8f5e9;
            font-weight: bold;
        }

        .percentage {
            font-size: 7px;
        }

        .footer {
            margin-top: 20px;
            font-size: 9px;
            color: #666;
        }

        @media print {
            body {
                padding: 5px;
            }

            @page {
                size: landscape;
                margin: 5mm;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Laporan Aduan Tahun {{ $year }}</h1>
        <p>Tarikh Jana: {{ $generatedDate }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th rowspan="2" class="category-col">Kategori Aduan</th>
                <th colspan="3" class="month-header">Jan</th>
                <th colspan="3" class="month-header">Feb</th>
                <th colspan="3" class="month-header">Mac</th>
                <th colspan="3" class="month-header">Apr</th>
                <th colspan="3" class="month-header">Mei</th>
                <th colspan="3" class="month-header">Jun</th>
                <th colspan="3" class="month-header">Jul</th>
                <th colspan="3" class="month-header">Ogos</th>
                <th colspan="3" class="month-header">Sep</th>
                <th colspan="3" class="month-header">Okt</th>
                <th colspan="3" class="month-header">Nov</th>
                <th colspan="3" class="month-header">Dis</th>
                <th colspan="3" class="total-header">Jumlah Keseluruhan</th>
            </tr>
            <tr>
                @for($i = 0; $i < 12; $i++)
                    <th>Bil.<br>Aduan</th>
                    <th>Aduan<br>Selesai</th>
                    <th>%<br>Selesai</th>
                @endfor
                <th>Bil.<br>Aduan</th>
                <th>Aduan<br>Selesai</th>
                <th>%<br>Selesai</th>
            </tr>
        </thead>
        <tbody>
            @php
                $months = ['jan', 'feb', 'mac', 'apr', 'mei', 'jun', 'jul', 'ogos', 'sep', 'okt', 'nov', 'dis'];
            @endphp
            @foreach($data as $row)
            <tr>
                <td class="category-col">{{ $row->cat_name }}</td>
                @foreach($months as $month)
                    @php
                        $bilAduan = $row->{$month . '_aduan'} ?? 0;
                        $selesai = $row->{$month . '_selesai'} ?? 0;
                        $peratus = $bilAduan > 0 ? round(($selesai / $bilAduan) * 100, 2) : 0;
                    @endphp
                    <td>{{ $bilAduan }}</td>
                    <td>{{ $selesai }}</td>
                    <td class="percentage">{{ $peratus }}%</td>
                @endforeach
                @php
                    $jumlahAduan = $row->jumlah_aduan ?? 0;
                    $jumlahSelesai = $row->jumlah_selesai ?? 0;
                    $jumlahPeratus = $jumlahAduan > 0 ? round(($jumlahSelesai / $jumlahAduan) * 100, 2) : 0;
                @endphp
                <td><strong>{{ $jumlahAduan }}</strong></td>
                <td><strong>{{ $jumlahSelesai }}</strong></td>
                <td class="percentage"><strong>{{ $jumlahPeratus }}%</strong></td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td class="category-col"><strong>Jumlah Keseluruhan Aduan</strong></td>
                @foreach($months as $month)
                    @php
                        $bilAduan = $totals[$month . '_aduan'] ?? 0;
                        $selesai = $totals[$month . '_selesai'] ?? 0;
                        $peratus = $bilAduan > 0 ? round(($selesai / $bilAduan) * 100, 2) : 0;
                    @endphp
                    <td><strong>{{ $bilAduan }}</strong></td>
                    <td><strong>{{ $selesai }}</strong></td>
                    <td class="percentage"><strong>{{ $peratus }}%</strong></td>
                @endforeach
                @php
                    $jumlahAduan = $totals['jumlah_aduan'] ?? 0;
                    $jumlahSelesai = $totals['jumlah_selesai'] ?? 0;
                    $jumlahPeratus = $jumlahAduan > 0 ? round(($jumlahSelesai / $jumlahAduan) * 100, 2) : 0;
                @endphp
                <td><strong>{{ $jumlahAduan }}</strong></td>
                <td><strong>{{ $jumlahSelesai }}</strong></td>
                <td class="percentage"><strong>{{ $jumlahPeratus }}%</strong></td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        <p><strong>Nota:</strong></p>
        <ul style="margin-left: 15px; margin-top: 5px;">
            <li>Bil. Aduan = Bilangan aduan dilog mengikut kategori</li>
            <li>Aduan Selesai = Bilangan aduan yang telah selesai (status = 6)</li>
            <li>% Selesai = (Aduan Selesai / Bil. Aduan) × 100</li>
        </ul>
    </div>
</body>
</html>