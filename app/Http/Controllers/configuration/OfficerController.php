<?php

namespace App\Http\Controllers\Configuration;

use App\Http\Controllers\Controller;
use App\Models\Officer;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class OfficerController extends Controller
{
    public function index()
    {
        return view('officers.index');
    }

    public function data(Request $request)
    {
        $officers = Officer::with(['subcategory.category', 'user'])
            ->select('officers.*');

        return DataTables::of($officers)
            ->addIndexColumn()
            ->addColumn('cat_name', function ($officer) {
                return $officer->subcategory->category->cat_name ?? '-';
            })
            ->addColumn('subcat_name', function ($officer) {
                return $officer->subcategory->subcat_name ?? '-';
            })
            ->addColumn('user_name', function ($officer) {
                return $officer->user->name ?? '-';
            })
            ->addColumn('action', function ($officer) {
                return '<div class="d-inline-block text-nowrap">
                    <button class="btn btn-sm btn-icon edit-record" data-id="' . $officer->id . '">
                        <i class="bx bx-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-icon delete-record text-danger" data-id="' . $officer->id . '">
                        <i class="bx bx-trash"></i>
                    </button>
                </div>';
            })
            ->rawColumns(['action'])
            ->make(true);
    }
}
