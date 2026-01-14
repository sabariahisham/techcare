<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sla;
use App\Models\User;
use App\Models\RefCategory;
use App\Models\RefSubCategory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SlaApiController extends Controller
{
  /**
   * Get SLA data for a specific category and subcategory
   */
  public function getSlaData($categoryId, $subcategoryId): JsonResponse
  {
    try {
      // Validate parameters
      $category = RefCategory::find($categoryId);
      $subCategory = RefSubCategory::find($subcategoryId);

      if (!$category || !$subCategory) {
        return response()->json([
          'success' => false,
          'message' => 'Kategori atau sub kategori tidak dijumpai'
        ], 404);
      }

      // Get SLA configurations for this category and subcategory
      $slas = Sla::where('cat_id', $categoryId)
        ->where('subcat_id', $subcategoryId)
        ->orderBy('sla_level')
        ->orderBy('sla_severity')
        ->get();

      if ($slas->isEmpty()) {
        return response()->json([
          'success' => false,
          'message' => 'Tiada konfigurasi SLA dijumpai untuk kategori dan sub kategori ini'
        ]);
      }

      // Format severity options
      $severities = [];
      $uniqueSeverities = $slas->unique('sla_severity');

      foreach ($uniqueSeverities as $sla) {
        $severityLabel = match ($sla->sla_severity) {
          'High' => 'High',
          'Medium' => 'Medium',
          'Low' => 'Low',
          default => ucfirst($sla->sla_severity)
        };

        $severities[] = [
          'value' => $sla->sla_severity,
          'label' => $severityLabel,
          'sla_id' => $sla->id,
          'sla_level' => $sla->sla_level,
          'sla_days' => $sla->sla_days
        ];
      }

      return response()->json([
        'success' => true,
        'severities' => $severities,
        'sla_configurations' => $slas
      ]);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Ralat memuat data SLA: ' . $e->getMessage()
      ], 500);
    }
  }

  /**
   * Get officers filtered by SLA criteria
   */
  public function getOfficersBySla($categoryId, $subcategoryId, $severity): JsonResponse
  {
    try {
      // Validate parameters
      $category = RefCategory::find($categoryId);
      $subCategory = RefSubCategory::find($subcategoryId);

      if (!$category || !$subCategory) {
        return response()->json([
          'success' => false,
          'message' => 'Kategori atau sub kategori tidak dijumpai'
        ], 404);
      }

      // Get SLA configuration for specific criteria
      $sla = Sla::where('cat_id', $categoryId)
        ->where('subcat_id', $subcategoryId)
        ->where('sla_severity', $severity)
        ->first();

      if (!$sla) {
        return response()->json([
          'success' => false,
          'message' => 'Konfigurasi SLA tidak dijumpai untuk kriteria ini'
        ], 404);
      }

      // For now, we'll filter officers based on their roles and potentially SLA level
      // This can be enhanced later to include more specific SLA-based officer assignments
      $baseRoles = ['Administrator', 'Pentadbir Sistem', 'Penyelia Helpdesk', 'Helpdesk On Duty', 'Pegawai Tugasan'];

      // Get officers based on SLA severity priority
      $officers = User::whereHas('roles', function ($query) use ($baseRoles, $sla) {
        $query->whereIn('name', $baseRoles);

        // Higher priority roles for High severity
        if ($sla->sla_severity === 'High') {
          $query->orWhereIn('name', ['Administrator', 'Pentadbir Sistem', 'Penyelia Helpdesk']);
        }
      })
        ->select('id', 'nama', 'email')
        ->orderByRaw("
                CASE
                    WHEN EXISTS (
                        SELECT 1 FROM model_has_roles mhr
                        INNER JOIN roles r ON mhr.role_id = r.id
                        WHERE mhr.model_id = users.id
                        AND r.name IN ('Administrator', 'Pentadbir Sistem')
                    ) THEN 1
                    WHEN EXISTS (
                        SELECT 1 FROM model_has_roles mhr
                        INNER JOIN roles r ON mhr.role_id = r.id
                        WHERE mhr.model_id = users.id
                        AND r.name = 'Penyelia Helpdesk'
                    ) THEN 2
                    WHEN EXISTS (
                        SELECT 1 FROM model_has_roles mhr
                        INNER JOIN roles r ON mhr.role_id = r.id
                        WHERE mhr.model_id = users.id
                        AND r.name = 'Helpdesk On Duty'
                    ) THEN 3
                    ELSE 4
                END
            ")
        ->orderBy('nama')
        ->get();

      return response()->json([
        'success' => true,
        'officers' => $officers,
        'sla_info' => [
          'level' => $sla->sla_level,
          'severity' => $sla->sla_severity,
          'days' => $sla->sla_days,
          'category' => $category->cat_name,
          'subcategory' => $subCategory->subcat_name
        ],
        'message' => "Pegawai tugasan disusun berdasarkan keutamaan untuk tahap keparahan '{$severity}'"
      ]);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Ralat memuat senarai pegawai: ' . $e->getMessage()
      ], 500);
    }
  }

  /**
   * Get officers from officers table based on category, subcategory, and severity
   */
  public function getOfficersByCategorySeverity(Request $request): JsonResponse
  {
    try {
      $categoryId = $request->query('cat_id');
      $subcategoryId = $request->query('subcat_id');
      $severity = $request->query('severity');

      // Validate required parameters
      if (!$categoryId || !$subcategoryId || !$severity) {
        return response()->json([
          'success' => false,
          'message' => 'Parameter tidak lengkap'
        ], 400);
      }

      // Get officers from officers table filtered by category, subcategory
      $officers = \Illuminate\Support\Facades\DB::table('officers')
        ->join('users', 'officers.user_id', '=', 'users.id')
        ->where('officers.cat_id', $categoryId)
        ->where('officers.subcat_id', $subcategoryId)
        ->whereNull('officers.deleted_at')
        ->select('users.id', 'users.nama', 'users.email')
        ->orderBy('users.nama')
        ->get();

      if ($officers->isEmpty()) {
        return response()->json([
          'success' => false,
          'message' => 'Tiada pegawai dijumpai untuk kategori dan sub kategori ini'
        ], 404);
      }

      return response()->json([
        'success' => true,
        'officers' => $officers,
        'message' => 'Pegawai berjaya dimuatkan berdasarkan kategori, sub kategori, dan tahap severity'
      ]);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Ralat memuat senarai pegawai: ' . $e->getMessage()
      ], 500);
    }
  }

  /**
   * Get all available officers (fallback method)
   */
  public function getAllOfficers(): JsonResponse
  {
    try {
      $officers = User::whereHas('roles', function ($query) {
        $query->whereIn('name', ['Administrator', 'Pentadbir Sistem', 'Penyelia Helpdesk', 'Helpdesk On Duty', 'Pegawai Tugasan']);
      })
        ->select('id', 'nama', 'email')
        ->orderBy('nama')
        ->get();

      return response()->json([
        'success' => true,
        'officers' => $officers
      ]);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Ralat memuat senarai pegawai: ' . $e->getMessage()
      ], 500);
    }
  }
}
