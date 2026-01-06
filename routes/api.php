<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SlaController;

Route::get('/user', function (Request $request) {
  return $request->user();
})->middleware('auth:sanctum');

// SLA related routes
Route::middleware(['auth:sanctum'])->group(function () {
  Route::get('/sla-data/{categoryId}/{subcategoryId}', [SlaController::class, 'getSlaData']);
  Route::get('/officers-by-sla/{categoryId}/{subcategoryId}/{severity}', [SlaController::class, 'getOfficersBySla']);
});
