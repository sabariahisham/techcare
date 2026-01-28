<?php

namespace App\Services\Dashboard\Widgets;

use App\Models\RefCategory;
use Illuminate\Support\Facades\DB;

/**
 * Kategori Widget
 * 
 * Calculates ticket distribution by category.
 * Returns data suitable for pie/donut chart visualization.
 * 
 * @include('dashboard/partials/chart-category')
 */
class KategoriWidget
{
    /**
     * Get ticket distribution by category
     * 
     * @return array
     */
    public function getData(): array
    {
        // Get all categories
        $allCategories = RefCategory::select('id', 'cat_name')
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->get();
        
        // Get ticket counts per category
        $categoryCounts = DB::table('tickets as t')
            ->join('ref_categories as rc', 't.cat_id', '=', 'rc.id')
            ->select('rc.id', DB::raw('COUNT(t.id) as total'))
            ->whereNull('t.deleted_at')
            ->whereNull('rc.deleted_at')
            ->groupBy('rc.id')
            ->pluck('total', 'id')
            ->toArray();
        
        // Build result with zero counts for categories without tickets
        $result = [];
        foreach ($allCategories as $category) {
            $result[] = [
                'category_id' => $category->id,
                'category' => $category->cat_name,
                'total' => $categoryCounts[$category->id] ?? 0
            ];
        }
        
        return $result;
    }
    
    /**
     * Get ticket distribution by category with percentage
     * 
     * @return array
     */
    public function getDataWithPercentage(): array
    {
        $data = $this->getData();
        $totalTickets = array_sum(array_column($data, 'total'));
        
        return array_map(function ($item) use ($totalTickets) {
            $percentage = $totalTickets > 0 
                ? round(($item['total'] / $totalTickets) * 100, 1) 
                : 0;
            
            return array_merge($item, ['percentage' => $percentage]);
        }, $data);
    }
}
