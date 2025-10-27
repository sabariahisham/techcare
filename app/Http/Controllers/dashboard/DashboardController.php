<?php

namespace App\Http\Controllers\dashboard;

use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
  public function index()
  {
    return view('dashboard.dashboard');
  }
}
