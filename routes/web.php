<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\apps\UserList;
use App\Http\Controllers\apps\AccessRoles;
use App\Http\Controllers\apps\AccessPermission;
use App\Http\Controllers\user_interface\Toasts;
use App\Http\Controllers\extended_ui\SweetAlert;
use App\Http\Controllers\Ticket\TicketController;
use App\Http\Controllers\Audit\AuditLogController;
use App\Http\Controllers\Audit\AuditTrailController;
use App\Http\Controllers\Ticket\TicketSLAController;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Ticket\TicketSearchController;
use App\Http\Controllers\Configuration\ModuleController;
use App\Http\Controllers\Ticket\TicketCommentController;
use App\Http\Controllers\Ticket\TicketAssignmentController;
use App\Http\Controllers\Ticket\TicketAttachmentController;
use App\Http\Controllers\Ticket\TicketChronologyController;
use App\Http\Controllers\Ticket\TicketDueDateController;
use App\Http\Controllers\Notifications\TicketStatusController;
use App\Http\Controllers\Configuration\UserManagementController;
use App\Http\Controllers\Configuration\RefCategoryManagementController;
use Laravel\Fortify\Http\Controllers\TwoFactorAuthenticatedSessionController;
use App\Http\Controllers\Reports\AduanTahunanController;
use App\Http\Controllers\Reports\StatistikLogAduanController;
use App\Http\Controllers\Reports\LaporanStatusAduanTahunanController;

// ============================================================================
// DYNAMIC MIDDLEWARE CONFIGURATION
// ============================================================================
// Automatically select middleware based on CAS_ENABLED in .env
// Toggle CAS: Set CAS_ENABLED=true (SATUID) or false (Local auth) in .env
// ============================================================================
$authMiddleware = config('cas.cas_enabled')
    ? ['cas.auth']
    : ['auth', config('jetstream.auth_session')];
// ============================================================================

// Test route to check CAS configuration
Route::get('/auth-status', function () {
  $casEnabled = config('cas.cas_enabled');
  $casService = config('cas.cas_client_service');
  $currentUrl = config('app.url');

  return response()->json([
    'cas_enabled' => $casEnabled,
    'authentication_mode' => $casEnabled ? 'SATUID (CAS)' : 'Local Laravel',
    'cas_service_url' => $casService,
    'app_url' => $currentUrl,
    'cas_hostname' => config('cas.cas_hostname'),
    'middleware' => $casEnabled ? 'cas.auth' : 'auth + jetstream',
    'user_authenticated' => Auth::check(),
    'current_user' => Auth::check() ? [
      'id' => Auth::user()->id,
      'nama' => Auth::user()->nama,
      'email' => Auth::user()->email
    ] : null
  ]);
})->name('auth.status');

// Test email route using Mailtrap
// run at browser: ../test-email
use Illuminate\Support\Facades\Mail;

Route::get('/test-email', function () {
  Mail::raw('Test email from Laravel', function ($message) {
    $message->to('test@example.com')
      ->subject('Test Mailtrap');
  });

  return 'Email sent!';
});

Route::get('/', function () {
  return redirect('/dashboard');
})->middleware($authMiddleware);

Route::get('/two-factor-challenge', [TwoFactorAuthenticatedSessionController::class, 'create'])
  ->middleware(['auth'])
  ->name('two-factor.login');

Route::post('/two-factor-challenge', [TwoFactorAuthenticatedSessionController::class, 'store'])
  ->middleware(['auth'])
  ->name('two-factor.login.store');

// ============================================================================
// DYNAMIC CAS/LOCAL AUTHENTICATION ROUTES
// ============================================================================
// Automatically switches between CAS (SATUID) and Local authentication
// based on CAS_ENABLED in .env file
// - CAS_ENABLED=true: Uses SATUID authentication
// - CAS_ENABLED=false: Uses standard Laravel/Jetstream authentication
// ============================================================================

// Session Management Routes (for session timeout handling)
Route::middleware($authMiddleware)->group(function () {
  // Ping endpoint to extend session and check authentication status
  Route::post('/session/ping', function () {
    return response()->json([
      'authenticated' => Auth::check(),
      'remaining' => config('session.lifetime') * 60 - (time() - session('_token_time', time())),
      'timestamp' => now()->toISOString()
    ]);
  })->name('session.ping');

  // Check session status without extending
  Route::get('/session/check', function () {
    $user = Auth::user();
    return response()->json([
      'authenticated' => Auth::check(),
      'user' => $user ? ['id' => $user->id, 'name' => $user->name] : null,
      'timestamp' => now()->toISOString()
    ]);
  })->name('session.check');
});

// CAS Logout Route (login page satuID)
if (config('cas.cas_enabled')) {
  Route::post('/cas-logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    // Redirect to SATUID logout
    return redirect(config('cas.cas_logout_url'));
  })->name('cas.logout');
}

#logout untuk  sistem techcare (login page)
// Route::post('/logout', function () {
//   Auth::logout();
//   request()->session()->invalidate();
//   request()->session()->regenerateToken();
//   return redirect('/login');
// })->name('logout');
// ============================================================================
// END DYNAMIC AUTHENTICATION ROUTES
// ============================================================================

// Dashboard Routes Group
Route::middleware($authMiddleware)->prefix('dashboard')->name('dashboard.')->group(function () {
  // Main dashboard page
  Route::get('/', [DashboardController::class, 'index'])->name('index');
  // Dashboard API endpoints
  Route::get('/current-status', [DashboardController::class, 'getCurrentStatus'])->name('current-status');
  Route::get('/status-tickets', [DashboardController::class, 'getStatusTickets'])->name('status-tickets');
  Route::get('/category-tickets', [DashboardController::class, 'getCategoryTickets'])->name('category-tickets');
  Route::get('/total-tickets', [DashboardController::class, 'getTotalTickets'])->name('total-tickets');
  Route::get('/monthly-trend', [DashboardController::class, 'getMonthlyTrends'])->name('monthly-trend');
  Route::get('/total-assigned', [DashboardController::class, 'getTotalAssigned'])->name('total-assigned');
  Route::get('/list-overdue', [DashboardController::class, 'getListOverdue'])->name('list-overdue');

  // New dashboard cards API endpoints
  Route::get('/today-logs-count', [DashboardController::class, 'getTodayLogsCount'])->name('today-logs-count');
  Route::get('/weekly-completed', [DashboardController::class, 'getWeeklyCompletedTickets'])->name('weekly-completed');
  Route::get('/pending-stats', [DashboardController::class, 'getPendingTicketsStats'])->name('pending-stats');
  Route::get('/overdue-sla-count', [DashboardController::class, 'getOverdueSLACount'])->name('overdue-sla-count');

  // Pegawai Tugasan specific endpoints (always filtered by assigned tasks)
  Route::get('/pegawai/today-logs-count', [DashboardController::class, 'getTodayLogsCountPegawai'])->name('pegawai.today-logs-count');
  Route::get('/pegawai/weekly-completed', [DashboardController::class, 'getWeeklyCompletedTicketsPegawai'])->name('pegawai.weekly-completed');
  Route::get('/pegawai/pending-stats', [DashboardController::class, 'getPendingTicketsStatsPegawai'])->name('pegawai.pending-stats');
  Route::get('/pegawai/overdue-sla-count', [DashboardController::class, 'getOverdueSLACountPegawai'])->name('pegawai.overdue-sla-count');
  Route::get('/pegawai/status-tickets', [DashboardController::class, 'getStatusTicketsPegawai'])->name('pegawai.status-tickets');
  Route::get('/pegawai/category-tickets', [DashboardController::class, 'getCategoryTicketsPegawai'])->name('pegawai.category-tickets');
  Route::get('/pegawai/list-overdue', [DashboardController::class, 'getListOverduePegawai'])->name('pegawai.list-overdue');
});

// API Routes for AJAX calls
Route::prefix('api')->middleware($authMiddleware)->group(function () {
  Route::get('/subcategories/{categoryId}', [DashboardController::class, 'getSubcategories'])->name('api.subcategories');

  // SLA-based filtering routes
  Route::get('/sla-data/{categoryId}/{subcategoryId}', [App\Http\Controllers\Api\SlaApiController::class, 'getSlaData'])->name('api.sla-data');
  Route::get('/officers-by-sla/{categoryId}/{subcategoryId}/{severity}', [App\Http\Controllers\Api\SlaApiController::class, 'getOfficersBySla'])->name('api.officers-by-sla');
  Route::get('/officers-by-category-severity', [App\Http\Controllers\Api\SlaApiController::class, 'getOfficersByCategorySeverity'])->name('api.officers-by-category-severity');
  Route::get('/officers', [App\Http\Controllers\Api\SlaApiController::class, 'getAllOfficers'])->name('api.officers');
});

// Legacy dashboard route (redirect to new grouped route)
// Route::middleware([
//   'auth',
//   config('jetstream.auth_session'),
// ])->get('/home', function () {
//   return redirect()->route('dashboard.index');
// })->name('home');

// ACL (Access Control List) Routes - Modules only (Users, Roles, Permissions use /admin/* routes below)
Route::middleware(['auth'])->group(function () {
  // modules (actively used by JavaScript)
  Route::get('/acl/module-list/index', [ModuleController::class, 'index'])->name('acl-module-list-index');
  Route::get('/modules', [ModuleController::class, 'getModules'])->name('modules.data');
  Route::post('/modules', [ModuleController::class, 'store'])->name('modules.store');
  Route::get('/modules/{id}/edit', [ModuleController::class, 'edit'])->name('modules.edit');
  Route::get('/modules/{id}/details', [ModuleController::class, 'getModuleDetails'])->name('modules.details');
  Route::post('/modules/update/{id}', [ModuleController::class, 'update'])->name('modules.update');
  Route::delete('/modules/{id}', [ModuleController::class, 'destroy'])->name('modules.destroy');

  // Legacy user management (for menu compatibility)
  Route::get('/user-management/index', [UserManagementController::class, 'index'])->name('user-management-index');
  Route::get('/user-management/data', [UserManagementController::class, 'getData'])->name('user-management.data');
  Route::get('/user-management/{id}/edit', [UserManagementController::class, 'edit'])->name('user-management.edit');
  Route::post('/user-management/update/{id}', [UserManagementController::class, 'update'])->name('user-management.update');
  Route::delete('/user-management/{id}', [UserManagementController::class, 'destroy'])->name('user-management.destroy');
  Route::post('/user-management/store', [UserManagementController::class, 'store'])->name('user-management.store');

  // Legacy ACL routes (for menu compatibility only - redirect to admin routes)
  // TODO: Update menu URLs in resources/menu/verticalMenu.json to use /admin/* instead of /acl/*
  Route::get('/acl/roles/index', function () {
    return redirect('/admin/roles');
  })->name('acl-roles-index');
  Route::get('/acl/permissions/index', function () {
    return redirect('/admin/permissions');
  })->name('acl-permissions-index');
});

// reference category management
Route::get('/ref-category-management/index', [App\Http\Controllers\Configuration\RefCategoryManagementController::class, 'index'])->name('ref-category-management-index');
Route::get('/ref-category-management/data', [App\Http\Controllers\Configuration\RefCategoryManagementController::class, 'getAllCategories'])->name('ref-category-management.data');
Route::post('/ref-category-management/check-duplicate', [App\Http\Controllers\Configuration\RefCategoryManagementController::class, 'checkDuplicate'])->name('ref-category-management.check-duplicate');
Route::post('/ref-category-management/store', [App\Http\Controllers\Configuration\RefCategoryManagementController::class, 'store'])->name('ref-category-management.store');
Route::get('/ref-category-management/{id}/edit', [App\Http\Controllers\Configuration\RefCategoryManagementController::class, 'edit'])->name('ref-category-management.edit');
Route::post('/ref-category-management/update/{id}', [App\Http\Controllers\Configuration\RefCategoryManagementController::class, 'update'])->name('ref-category-management.update');
Route::delete('/ref-category-management/{id}', [App\Http\Controllers\Configuration\RefCategoryManagementController::class, 'destroy'])->name('ref-category-management.destroy');
Route::delete('/ref-category-management/{id}/force', [App\Http\Controllers\Configuration\RefCategoryManagementController::class, 'forceDestroy'])->name('ref-category-management.force-destroy');
Route::get('/categories/{id}/subcategories', [App\Http\Controllers\Configuration\RefCategoryManagementController::class, 'getSubcategories'])->name('categories.subcategories');

// reference sub-category management
Route::get('/ref-sub-category-management/index', [App\Http\Controllers\Configuration\RefSubCategoryManagementController::class, 'index'])->name('ref-sub-category-management-index');
Route::get('/ref-sub-category-management/data', [App\Http\Controllers\Configuration\RefSubCategoryManagementController::class, 'data'])->name('ref-sub-category-management-data');
Route::post('/ref-sub-category-management/check-duplicate', [App\Http\Controllers\Configuration\RefSubCategoryManagementController::class, 'checkDuplicate'])->name('ref-sub-category-management.check-duplicate');
Route::get('/ref-subcategory-management/by-category/{categoryId}', [App\Http\Controllers\Configuration\RefSubCategoryManagementController::class, 'getByCategory'])->name('ref-subcategory.by-category');
Route::resource('ref-sub-category-management', App\Http\Controllers\Configuration\RefSubCategoryManagementController::class)->except(['index']);

// reference sla and severity management
Route::get('/sla-severity/index', [App\Http\Controllers\Configuration\SlaController::class, 'index'])->name('sla-severity-index');
Route::get('/sla-severity/data', [App\Http\Controllers\Configuration\SlaController::class, 'data'])->name('sla-severity-data');
Route::post('/sla-severity/check-duplicate', [App\Http\Controllers\Configuration\SlaController::class, 'checkDuplicate'])->name('sla-severity-check-duplicate');
Route::post('/sla-severity', [App\Http\Controllers\Configuration\SlaController::class, 'store'])->name('sla-severity-store');
Route::get('/sla-severity/{id}/edit', [App\Http\Controllers\Configuration\SlaController::class, 'edit'])->name('sla-severity-edit');
Route::put('/sla-severity/{id}', [App\Http\Controllers\Configuration\SlaController::class, 'update'])->name('sla-severity-update');
Route::delete('/sla-severity/{id}', [App\Http\Controllers\Configuration\SlaController::class, 'destroy'])->name('sla-severity-destroy');

// appointed officer management
Route::middleware(['auth'])->group(function () {
  Route::get('/officer/index', [App\Http\Controllers\Configuration\OfficerController::class, 'index'])->name('officer-index');
  Route::get('/officer/data', [App\Http\Controllers\Configuration\OfficerController::class, 'data'])->name('officer-data');
  Route::post('/officer/store', [App\Http\Controllers\Configuration\OfficerController::class, 'store'])->name('officer-store');
  Route::get('/officer/{id}/edit', [App\Http\Controllers\Configuration\OfficerController::class, 'edit'])->name('officer-edit');
  Route::put('/officer/{id}', [App\Http\Controllers\Configuration\OfficerController::class, 'update'])->name('officer-update');
  Route::get('/officer/{id}/preview-delete', [App\Http\Controllers\Configuration\OfficerController::class, 'previewDelete'])->name('officer-preview-delete');
  Route::delete('/officer/{id}', [App\Http\Controllers\Configuration\OfficerController::class, 'destroy'])->name('officer-destroy');
});

// report management
Route::middleware(['auth'])->group(function () {
  Route::get('/report/index', [App\Http\Controllers\Reports\ReportLogTicketController::class, 'index'])->name('report-index');
  Route::get('/report/data', [App\Http\Controllers\Reports\ReportLogTicketController::class, 'data'])->name('report-data');
  Route::get('/report/senarai-log-tiket', [App\Http\Controllers\Reports\ReportLogTicketController::class, 'logTicket'])->name('report-log-ticket');
  Route::get('/report/senarai-log-tiket/data', [App\Http\Controllers\Reports\ReportLogTicketController::class, 'logTicketData'])->name('report-log-ticket-data');
  Route::get('/report/senarai-log-tiket/export-excel', [App\Http\Controllers\Reports\ReportLogTicketController::class, 'exportExcel'])->name('report-log-ticket-export-excel');
  Route::get('/report/senarai-log-tiket/export-pdf', [App\Http\Controllers\Reports\ReportLogTicketController::class, 'exportPdf'])->name('report-log-ticket-export-pdf');

  // Prestasi Pegawai Tugasan
  Route::get('/report/prestasi-pegawai-tugasan', [App\Http\Controllers\Reports\PrestasiPegtugasController::class, 'index'])->name('report-prestasi-pegtugas');
  Route::get('/report/prestasi-pegawai-tugasan/data', [App\Http\Controllers\Reports\PrestasiPegtugasController::class, 'data'])->name('report-prestasi-pegtugas-data');
  Route::get('/report/prestasi-pegawai-tugasan/export-excel', [App\Http\Controllers\Reports\PrestasiPegtugasController::class, 'exportExcel'])->name('report-prestasi-pegtugas-export-excel');
  Route::get('/report/prestasi-pegawai-tugasan/export-pdf', [App\Http\Controllers\Reports\PrestasiPegtugasController::class, 'exportPdf'])->name('report-prestasi-pegtugas-export-pdf');

  //Laporan Aduan Tahunan
  Route::get('/report/aduan-tahunan', [App\Http\Controllers\Reports\AduanTahunanController::class, 'index'])->name('report-aduan-tahunan');
  Route::post('/report/aduan-tahunan/data', [App\Http\Controllers\Reports\AduanTahunanController::class, 'data'])->name('report-aduan-tahunan-data');
  Route::get('/report/aduan-tahunan/export-excel', [App\Http\Controllers\Reports\AduanTahunanController::class, 'exportExcel'])->name('report-aduan-tahunan-export-excel');
  Route::get('/report/aduan-tahunan/export-pdf', [App\Http\Controllers\Reports\AduanTahunanController::class, 'exportPdf'])->name('report-aduan-tahunan-export-pdf');

  // Statistik Log Aduan
  Route::get('/report/statistik-log', [App\Http\Controllers\Reports\StatistikLogAduanController::class, 'index'])->name('report-statistik-log');
  Route::get('/report/statistik-log/data', [App\Http\Controllers\Reports\StatistikLogAduanController::class, 'getData'])->name('report-statistik-log-data');
  Route::get('/report/statistik-log/chart', [App\Http\Controllers\Reports\StatistikLogAduanController::class, 'getChart'])->name('report-statistik-log-chart');
  Route::get('/report/statistik-log/export-excel', [App\Http\Controllers\Reports\StatistikLogAduanController::class, 'exportExcel'])->name('report-statistik-log-export-excel');
  Route::get('/report/statistik-log/export-pdf', [App\Http\Controllers\Reports\StatistikLogAduanController::class, 'exportPdf'])->name('report-statistik-log-export-pdf');

  //laporan status aduan tahunan
  Route::get('/report/laporan-status-aduan-tahunan', [App\Http\Controllers\Reports\LaporanStatusAduanTahunanController::class, 'index'])->name('report-laporan-status-aduan-tahunan');
  Route::post('/report/laporan-status-aduan-tahunan/data', [App\Http\Controllers\Reports\LaporanStatusAduanTahunanController::class, 'data'])->name('report-laporan-status-aduan-tahunan-data');
  Route::get('/report/laporan-status-aduan-tahunan/export-excel', [App\Http\Controllers\Reports\LaporanStatusAduanTahunanController::class, 'exportExcel'])->name('report-laporan-status-aduan-tahunan-export-excel');
  Route::get('/report/laporan-status-aduan-tahunan/export-pdf', [App\Http\Controllers\Reports\LaporanStatusAduanTahunanController::class, 'exportPdf'])->name('report-laporan-status-aduan-tahunan-export-pdf');

  //Senarai rekod laporan
  Route::get('/report/rekod-laporan', [App\Http\Controllers\Reports\RekodLaporanController::class, 'index'])->name('report-rekod-laporan');
  Route::get('/report/rekod-laporan/ringkasan', [App\Http\Controllers\Reports\RekodLaporanController::class, 'ringkasan'])->name('report-rekod-laporan-ringkasan');
  Route::get('/report/rekod-laporan/perincian', [App\Http\Controllers\Reports\RekodLaporanController::class, 'perincian'])->name('report-rekod-laporan-perincian');

});
// references
Route::get('/app/access-roles', [AccessRoles::class, 'index'])->name('app-access-roles');
Route::get('/app/access-permission', [AccessPermission::class, 'index'])->name('app-access-permission');
Route::get('/ui/toasts', [Toasts::class, 'index'])->name('ui-toasts');
Route::get('/extended/ui-sweetalert2', [SweetAlert::class, 'index'])->name('extended-ui-sweetalert2');
Route::get('/app/user/list', [UserList::class, 'index'])->name('app-user-list');

// knowledgebase
Route::middleware(['auth'])->prefix('knowledgebase')->group(function () {
  Route::get('/', [App\Http\Controllers\Knowledgebase\KnowledgeBaseController::class, 'index'])->name('knowledgebase.index');
  Route::get('/data', [App\Http\Controllers\Knowledgebase\KnowledgeBaseController::class, 'getData'])->name('knowledgebase.data');
  Route::post('/', [App\Http\Controllers\Knowledgebase\KnowledgeBaseController::class, 'store'])->name('knowledgebase.store');
  Route::get('/{article}/details', [App\Http\Controllers\Knowledgebase\KnowledgeBaseController::class, 'getDetails'])->name('knowledgebase.details');
  Route::post('/{article}/soft-delete', [App\Http\Controllers\Knowledgebase\KnowledgeBaseController::class, 'softDelete'])->name('knowledgebase.soft-delete');
  Route::get('/{article}', [App\Http\Controllers\Knowledgebase\KnowledgeBaseController::class, 'show'])->name('knowledgebase.show');
  Route::get('/{article}/edit', [App\Http\Controllers\Knowledgebase\KnowledgeBaseController::class, 'edit'])->name('knowledgebase.edit');
  Route::put('/{article}', [App\Http\Controllers\Knowledgebase\KnowledgeBaseController::class, 'update'])->name('knowledgebase.update');
  Route::delete('/{article}', [App\Http\Controllers\Knowledgebase\KnowledgeBaseController::class, 'destroy'])->name('knowledgebase.destroy');
  Route::delete('/document/{document}', [App\Http\Controllers\Knowledgebase\KnowledgeBaseController::class, 'deleteDocument'])->name('knowledgebase.document.delete');
  Route::get('/document/{document}/download', [App\Http\Controllers\Knowledgebase\KnowledgeBaseController::class, 'downloadDocument'])->name('knowledgebase.document.download');
});

// Main Admin Management Routes (Users, Roles, Permissions)
Route::middleware(['auth'])->group(function () {

  // User Management Routes (used by JavaScript)
  Route::prefix('admin/users')->name('admin.users.')->group(function () {
    Route::get('/search', [UserManagementController::class, 'search'])->name('search');
    // Add other user routes as needed when implemented
  });

  // Role Management Routes (actively used)
  Route::prefix('admin/roles')->name('admin.roles.')->group(function () {
    Route::get('/', [App\Http\Controllers\Configuration\RoleController::class, 'index'])->name('index');
    Route::get('/data', [App\Http\Controllers\Configuration\RoleController::class, 'getData'])->name('data');
    Route::get('/statistics', [App\Http\Controllers\Configuration\RoleController::class, 'getStatistics'])->name('statistics');
    Route::post('/', [App\Http\Controllers\Configuration\RoleController::class, 'store'])->name('store');
    Route::get('/{role}/edit', [App\Http\Controllers\Configuration\RoleController::class, 'edit'])->name('edit');
    Route::put('/{role}', [App\Http\Controllers\Configuration\RoleController::class, 'update'])->name('update');
    Route::delete('/{role}', [App\Http\Controllers\Configuration\RoleController::class, 'destroy'])->name('destroy');
    Route::post('/bulk-delete', [App\Http\Controllers\Configuration\RoleController::class, 'bulkDelete'])->name('bulk-delete');
    Route::get('/{role}/permissions', [App\Http\Controllers\Configuration\RoleController::class, 'getRolePermissions'])->name('permissions');
    Route::delete('/{role}/users/{user}', [App\Http\Controllers\Configuration\RoleController::class, 'removeUserFromRole'])->name('remove-user');
    Route::post('/{role}/users', [App\Http\Controllers\Configuration\RoleController::class, 'addUserToRole'])->name('add-user');
  });

  // Permission Management Routes (actively used)
  Route::prefix('admin/permissions')->name('admin.permissions.')->group(function () {
    Route::get('/', [App\Http\Controllers\Configuration\PermissionController::class, 'index'])->name('index');
    Route::get('/data', [App\Http\Controllers\Configuration\PermissionController::class, 'getData'])->name('data');
    Route::post('/', [App\Http\Controllers\Configuration\PermissionController::class, 'store'])->name('store');
    Route::get('/{permission}/edit', [App\Http\Controllers\Configuration\PermissionController::class, 'edit'])->name('edit');
    Route::put('/{permission}', [App\Http\Controllers\Configuration\PermissionController::class, 'update'])->name('update');
    Route::delete('/{permission}', [App\Http\Controllers\Configuration\PermissionController::class, 'destroy'])->name('destroy');
  });
});

// Impersonation Routes
Route::middleware(['auth'])->group(function () {
  Route::get('/impersonate/user/{user}', function ($user) {
    $userToImpersonate = App\Models\User::findOrFail($user);
    $currentUser = Auth::user();

    if (!$currentUser->canImpersonate() || !$userToImpersonate->canBeImpersonated()) {
      abort(403, 'Unauthorized action.');
    }

    $currentUser->impersonate($userToImpersonate);

    return redirect('/dashboard')->with('impersonate_success', 'You are now impersonating ' . $userToImpersonate->nama);
  })->name('impersonate');

  Route::get('/impersonate/leave', function () {
    $currentUser = Auth::user();

    if ($currentUser->isImpersonated()) {
      $currentUser->leaveImpersonation();
    }

    return redirect('/dashboard')->with('impersonate_leave', 'You have stopped impersonating.');
  })->name('impersonate.leave');

  //Route untuk audit trail - senarai audit trail
  route::middleware(['auth'])->group(function () {
    Route::get('/audit-trail', [AuditTrailController::class, 'index'])->name('audit.index');
    Route::get('/audit-trail/data', [AuditTrailController::class, 'getAuditData'])->name('audit.data');
    Route::get('/audit-trail/detail/{audit}', [AuditTrailController::class, 'getAuditDetail'])->name('audit.detail');
  });

  //route untuk  modul audit trails -log keseluruhan
  Route::prefix('audit/logs')->middleware('auth')->group(function () {

    // Senarai fail log harian
    Route::get('/', [AuditLogController::class, 'index'])
      ->name('audit.logs.index');

    // Papar log mengikut tarikh
    Route::get('/view/{date}', [AuditLogController::class, 'show'])
      ->name('audit.logs.show');

    // Download log
    Route::get('/download/{date}', [AuditLogController::class, 'download'])
      ->name('audit.logs.download');
  });
});

// Tickets Routes Group: Core ticket operations (TicketController only)
// Route::middleware(['auth'])->prefix('tickets')->group(function () {

// Using dynamic $authMiddleware for CAS/Local authentication
Route::middleware($authMiddleware)->prefix('tickets')->group(function () {
  Route::get('/', [TicketController::class, 'index'])->name('tickets.index');
  Route::get('/data', [TicketController::class, 'data'])->name('tickets.data');
  Route::post('/', [TicketController::class, 'store'])->name('tickets.store');
  Route::get('/{uuid}', [TicketController::class, 'show'])->name('tickets.show');
  Route::get('/{uuid}/edit', [TicketController::class, 'edit'])->name('tickets.edit');
  Route::put('/{uuid}', [TicketController::class, 'update'])->name('tickets.update');
  Route::delete('/{uuid}', [TicketController::class, 'destroy'])->name('tickets.destroy');
  Route::post('/{uuid}/update-status', [TicketController::class, 'updateTicketStatus'])->name('tickets.update-status');
});

// Ticket Assignment Routes Group
Route::middleware($authMiddleware)->prefix('ticket-assignments')->group(function () {
  Route::put('/{uuid}/update', [TicketAssignmentController::class, 'updateTicketAssignment'])->name('ticket-assignments.update');
  Route::post('/{uuid}/assign', [TicketAssignmentController::class, 'assignTicket'])->name('tickets.assign');
  Route::get('/{uuid}/assignees', [TicketAssignmentController::class, 'getAssignees'])->name('tickets.assignees');
  Route::get('/available-assignees', [TicketAssignmentController::class, 'getAvailableAssignees'])->name('tickets.available-assignees');
  Route::get('/available-assignees-by-sla', [TicketAssignmentController::class, 'getAvailableAssigneesBySLA'])->name('tickets.available-assignees-by-sla');
  Route::get('/{uuid}/history-comments', [TicketAssignmentController::class, 'getHistoryComment'])->name('tickets.history-comments');
});

// Ticket Search Routes Group
Route::middleware($authMiddleware)->prefix('ticket-search')->group(function () {
  Route::post('/user-by-name', [TicketSearchController::class, 'searchUserByName'])->name('search.user.by.name');
  Route::post('/user-by-email', [TicketSearchController::class, 'searchUserByEmail'])->name('search.user.by.email');
  Route::post('/user-autocomplete', [TicketSearchController::class, 'searchUserAutocomplete'])->name('search.user.autocomplete');
});

// Subcategories by category (ticket form)
Route::get('/get-subcategory/{categoryId}', [RefCategoryManagementController::class, 'getSubcategories'])->name('get.subcategory');

// Ticket Attachment Routes Group
Route::middleware($authMiddleware)->prefix('ticket-attachments')->group(function () {
  Route::post('/upload', [TicketAttachmentController::class, 'upload'])->name('ticket-attachments.upload');
  Route::delete('/{uuid}', [TicketAttachmentController::class, 'destroy'])->name('ticket-attachments.destroy');
  Route::get('/{uuid}/download', [TicketAttachmentController::class, 'download'])->name('ticket-attachments.download');
});

// Ticket Chronology Routes Group
Route::middleware($authMiddleware)->prefix('ticket-chronology')->group(function () {
  Route::get('/{uuid}/data', [TicketChronologyController::class, 'getChronology'])->name('ticket-chronology.data');
  Route::post('/{uuid}/store', [TicketChronologyController::class, 'store'])->name('ticket-chronology.store');
  Route::get('/{uuid}/statistics', [TicketChronologyController::class, 'getStatistics'])->name('ticket-chronology.statistics');
});

// Ticket Comment Routes Group
Route::middleware($authMiddleware)->prefix('ticket-comments')->group(function () {
  Route::post('/{uuid}/add-comment', [TicketCommentController::class, 'store'])->name('tickets.add-comment');
  Route::post('/{uuid}/store', [TicketCommentController::class, 'store'])->name('ticket-comments.store');
  Route::get('/{uuid}/data', [TicketCommentController::class, 'getComments'])->name('ticket-comments.data');
});

// Ticket Assignment Routes Group
Route::middleware($authMiddleware)->prefix('ticket-assignments')->group(function () {
  Route::post('/{uuid}/assign', [TicketAssignmentController::class, 'assignTicket'])->name('ticket-assignments.assign');
  Route::get('/{uuid}/assignees', [TicketAssignmentController::class, 'getAssignees'])->name('ticket-assignments.assignees');
  Route::get('/available-assignees', [TicketAssignmentController::class, 'getAvailableAssignees'])->name('ticket-assignments.available-assignees');
  Route::get('/available-assignees-by-sla', [TicketAssignmentController::class, 'getAvailableAssigneesBySLA'])->name('ticket-assignments.available-assignees-by-sla');
});

// Ticket Email Notification Routes Group
Route::middleware($authMiddleware)->prefix('ticket-emails')->group(function () {
  Route::post('/send-notification/{uuid}', [TicketStatusController::class, 'sendNotification'])->name('ticket-emails.send-notification');
});

// Ticket SLA Monitoring Routes Group
Route::middleware(['auth'])->prefix('ticket-sla')->group(function () {
  Route::get('/overdue', [TicketSLAController::class, 'getOverdueTickets'])->name('ticket-sla.overdue');
  Route::get('/statistics', [TicketSLAController::class, 'getSlaStatistics'])->name('ticket-sla.statistics');
});

// Ticket Cron Jobs Routes Group
Route::middleware($authMiddleware)->prefix('ticket-duedate')->group(function () {
  Route::post('/check-sla', [TicketDueDateController::class, 'checkSla'])->name('ticket-duedate.check-sla');
});
