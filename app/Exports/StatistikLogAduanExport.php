<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class StatistikLogAduanExport implements FromView, WithTitle, ShouldAutoSize
{
    protected $data;
    protected $categories;
    protected $year;

    public function __construct($data, $categories, $year)
    {
        $this->data = $data;
        $this->categories = $categories;
        $this->year = $year;
    }

    public function view(): View
    {
        return view('report.statistik-log-excel', [
            'data' => $this->data,
            'categories' => $this->categories,
            'year' => $this->year
        ]);
    }

    public function title(): string
    {
        return 'Statistik Log ' . $this->year;
    }
}