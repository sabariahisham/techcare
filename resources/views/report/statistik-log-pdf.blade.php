<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Statistik Log Aduan {{ $year }}</title>
  <style>
    body { font-family: Arial, sans-serif; font-size: 9px; }
    h2 { text-align: center; margin-bottom: 20px; font-size: 14px; }
    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    th, td { border: 1px solid #ddd; padding: 5px; text-align: center; }
    th { background-color: #0d6efd; color: white; font-weight: bold; vertical-align: middle; }
    th.kategori-header { font-size: 10px; }
    td:first-child { text-align: left; font-weight: 600; }
    tfoot td { background-color: #f8f9fa; font-weight: bold; }
  </style>
</head>
<body>
  <h2>STATISTIK LOG ADUAN ICT {{ $year }}</h2>
  <table>
    <thead>
      <tr>
        <th rowspan="2">Bulan</th>
        <th colspan="{{ count($categories) }}" class="kategori-header">KATEGORI</th>
        <th rowspan="2">Jumlah</th>
      </tr>
      <tr>
        @foreach($categories as $category)
          <th>{{ $category->cat_name }}</th>
        @endforeach
      </tr>
    </thead>
    <tbody>
      @php
        $months = [
          'Januari', 'Februari', 'Mac', 'April', 'Mei', 'Jun',
          'Julai', 'Ogos', 'September', 'Oktober', 'November', 'Disember'
        ];
        $totals = [];
        foreach($categories as $cat) {
          $totals[$cat->id] = 0;
        }
        $grandTotal = 0;
      @endphp

      @foreach($data as $row)
      <tr>
        <td>{{ $row['bulan'] }}</td>
        @foreach($categories as $category)
          @php
            $value = $row['cat_' . $category->id] ?? 0;
            $totals[$category->id] += $value;
          @endphp
          <td>{{ $value }}</td>
        @endforeach
        @php
          $grandTotal += $row['jumlah'];
        @endphp
        <td style="font-weight: bold;">{{ $row['jumlah'] }}</td>
      </tr>
      @endforeach
    </tbody>
    <tfoot>
      <tr>
        <td>JUMLAH</td>
        @foreach($categories as $category)
          <td>{{ $totals[$category->id] }}</td>
        @endforeach
        <td>{{ $grandTotal }}</td>
      </tr>
    </tfoot>
  </table>
</body>
</html>