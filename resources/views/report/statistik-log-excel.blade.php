<table>
  <thead>
    <tr>
      <th colspan="{{ count($categories) + 2 }}" style="text-align:center; font-weight:bold; font-size:16px; background-color:#0d6efd; color:white;">
        STATISTIK LOG ADUAN ICT {{ $year }}
      </th>
    </tr>
    <tr>
      <th rowspan="2" style="background-color:#0d6efd; color:white; font-weight:bold; vertical-align:middle;">Bulan</th>
      <th colspan="{{ count($categories) }}" style="background-color:#0d6efd; color:white; font-weight:bold; text-align:center;">KATEGORI</th>
      <th rowspan="2" style="background-color:#0d6efd; color:white; font-weight:bold; text-align:center; vertical-align:middle;">Jumlah</th>
    </tr>
    <tr>
      @foreach($categories as $category)
        <th style="background-color:#0d6efd; color:white; font-weight:bold; text-align:center;">{{ $category->cat_name }}</th>
      @endforeach
    </tr>
  </thead>
  <tbody>
    @php
      $totals = [];
      foreach($categories as $cat) {
        $totals[$cat->id] = 0;
      }
      $grandTotal = 0;
    @endphp

    @foreach($data as $row)
    <tr>
      <td style="font-weight:600;">{{ $row['bulan'] }}</td>
      @foreach($categories as $category)
        @php
          $value = $row['cat_' . $category->id] ?? 0;
          $totals[$category->id] += $value;
        @endphp
        <td style="text-align:center;">{{ $value }}</td>
      @endforeach
      @php
        $grandTotal += $row['jumlah'];
      @endphp
      <td style="text-align:center; font-weight:bold;">{{ $row['jumlah'] }}</td>
    </tr>
    @endforeach
  </tbody>
  <tfoot>
    <tr style="background-color:#f8f9fa; font-weight:bold;">
      <td>JUMLAH</td>
      @foreach($categories as $category)
        <td style="text-align:center;">{{ $totals[$category->id] }}</td>
      @endforeach
      <td style="text-align:center;">{{ $grandTotal }}</td>
    </tr>
  </tfoot>
</table>