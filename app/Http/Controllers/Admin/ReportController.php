<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetAssignment;
use App\Models\License;
use App\Models\MaintenanceRecord;
use App\Models\StationaryItem;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class ReportController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('Admin/Reports/Index', [
            'reportTypes' => $this->getReportTypes(),
            'dateRanges' => $this->getDateRanges(),
        ]);
    }

    public function assetReport(Request $request)
    {
        dd($request->all());
        $request->validate([
            'date_range' => 'required|string',
            'report_type' => 'required|string',
        ]);

        $dateRange = $this->getDateRange($request->date_range);

        // Debug: Log the date range and check data
        Log::info('Asset Report Request', [
            'date_range' => $request->date_range,
            'date_range_actual' => $dateRange,
            'report_type' => $request->report_type,
        ]);

        // Debug: Check if we have any assets
        $assetCount = Asset::whereBetween('created_at', $dateRange)->count();
        Log::info('Asset count in date range: '.$assetCount);

        $report = [
            'title' => 'Asset Management Report',
            'period' => $this->getPeriodLabel($request->date_range),
            'generated_at' => now()->format('Y-m-d H:i:s'),
            'summary' => $this->getAssetSummary($dateRange),
            'status_distribution' => $this->getAssetStatusDistribution($dateRange),
            'assignment_analytics' => $this->getAssignmentAnalytics($dateRange),
            'maintenance_analytics' => $this->getMaintenanceAnalytics($dateRange),
            'recent_assignments' => $this->getRecentAssignments($dateRange),
            'top_models' => $this->getTopModels($dateRange),
        ];

        // Debug: Log the final report structure
        Log::info('Generated Report', $report);

        if ($request->has('export') && $request->export === 'true') {
            return $this->exportReport($report, 'asset-report');
        }

        return Inertia::render('Admin/Reports/AssetReport', [
            'report' => $report,
            'filters' => $request->only(['date_range', 'report_type']),
        ]);
    }

    /**
     * Generate license report
     */
    public function licenseReport(Request $request)
    {
        $request->validate([
            'date_range' => 'required|string',
            'report_type' => 'required|string',
        ]);

        $dateRange = $this->getDateRange($request->date_range);

        $report = [
            'title' => 'Software License Report',
            'period' => $this->getPeriodLabel($request->date_range),
            'generated_at' => now()->format('Y-m-d H:i:s'),
            'summary' => $this->getLicenseSummary($dateRange),
            'status_distribution' => $this->getLicenseStatusDistribution($dateRange),
            'manufacturer_breakdown' => $this->getManufacturerBreakdown($dateRange),
            'expiration_analytics' => $this->getExpirationAnalytics($dateRange),
            'low_stock_alerts' => $this->getLowStockLicenses($dateRange),
            'recently_added' => $this->getRecentlyAddedLicenses($dateRange),
        ];

        if ($request->has('export') && $request->export === 'true') {
            return $this->exportReport($report, 'license-report');
        }

        return Inertia::render('Admin/Reports/LicenseReport', [
            'report' => $report,
            'filters' => $request->only(['date_range', 'report_type']),
        ]);
    }

    /**
     * Generate user activity report
     */
    public function userActivityReport(Request $request)
    {
        $request->validate([
            'date_range' => 'required|string',
            'report_type' => 'required|string',
        ]);

        $dateRange = $this->getDateRange($request->date_range);

        $report = [
            'title' => 'User Activity Report',
            'period' => $this->getPeriodLabel($request->date_range),
            'generated_at' => now()->format('Y-m-d H:i:s'),
            'user_statistics' => $this->getUserStatistics($dateRange),
            'asset_assignments_by_user' => $this->getAssetAssignmentsByUser($dateRange),
            'recent_activities' => $this->getRecentUserActivities($dateRange),
            'department_breakdown' => $this->getDepartmentBreakdown($dateRange),
        ];

        if ($request->has('export') && $request->export === 'true') {
            return $this->exportReport($report, 'user-activity-report');
        }

        return Inertia::render('Admin/Reports/UserActivityReport', [
            'report' => $report,
            'filters' => $request->only(['date_range', 'report_type']),
        ]);
    }

    /**
     * Generate stationary items report
     */
    public function stationaryReport(Request $request)
    {
        $request->validate([
            'date_range' => 'required|string',
            'report_type' => 'required|string',
        ]);

        $dateRange = $this->getDateRange($request->date_range);

        $report = [
            'title' => 'Stationary Items Report',
            'period' => $this->getPeriodLabel($request->date_range),
            'generated_at' => now()->format('Y-m-d H:i:s'),
            'summary' => $this->getStationarySummary($dateRange),
            'stock_analytics' => $this->getStockAnalytics($dateRange),
            'movement_analytics' => $this->getMovementAnalytics($dateRange),
            'low_stock_items' => $this->getLowStockStationaryItems($dateRange),
            'top_moving_items' => $this->getTopMovingItems($dateRange),
        ];

        if ($request->has('export') && $request->export === 'true') {
            return $this->exportReport($report, 'stationary-report');
        }

        return Inertia::render('Admin/Reports/StationaryReport', [
            'report' => $report,
            'filters' => $request->only(['date_range', 'report_type']),
        ]);
    }

    /**
     * Generate comprehensive dashboard report
     */
    public function dashboardReport(Request $request)
    {
        $dateRange = $this->getDateRange($request->get('date_range', 'last_30_days'));

        $report = [
            'title' => 'Comprehensive Dashboard Report',
            'period' => $this->getPeriodLabel($request->get('date_range', 'last_30_days')),
            'generated_at' => now()->format('Y-m-d H:i:s'),
            'overview' => $this->getDashboardOverview($dateRange),
            'asset_metrics' => $this->getAssetMetrics($dateRange),
            'license_metrics' => $this->getLicenseMetrics($dateRange),
            'user_metrics' => $this->getUserMetrics($dateRange),
            'stationary_metrics' => $this->getStationaryMetrics($dateRange),
            'alerts' => $this->getSystemAlerts($dateRange),
        ];

        if ($request->has('export') && $request->export === 'true') {
            return $this->exportReport($report, 'dashboard-report');
        }

        return Inertia::render('Admin/Reports/DashboardReport', [
            'report' => $report,
            'filters' => $request->only(['date_range']),
        ]);
    }

    /**
     * Asset Report Methods
     */
    private function getAssetSummary($dateRange)
    {
        return [
            'total_assets' => Asset::whereBetween('created_at', $dateRange)->count(),
            'assigned_assets' => Asset::where('status', 'assigned')->whereBetween('created_at', $dateRange)->count(),
            'available_assets' => Asset::where('status', 'available')->whereBetween('created_at', $dateRange)->count(),
            'maintenance_assets' => Asset::where('status', 'maintenance')->whereBetween('created_at', $dateRange)->count(),
            'total_value' => Asset::whereBetween('created_at', $dateRange)->sum('purchase_cost'),
            'assets_added_this_period' => Asset::whereBetween('created_at', $dateRange)->count(),
        ];
    }

    private function getAssetStatusDistribution($dateRange)
    {
        return Asset::whereBetween('created_at', $dateRange)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get()
            ->mapWithKeys(fn ($item) => [$item->status => $item->count]);
    }

    private function getAssignmentAnalytics($dateRange)
    {
        return [
            'total_assignments' => AssetAssignment::whereBetween('assigned_at', $dateRange)->count(),
            'active_assignments' => AssetAssignment::whereNull('returned_at')->whereBetween('assigned_at', $dateRange)->count(),
            'completed_assignments' => AssetAssignment::whereNotNull('returned_at')->whereBetween('assigned_at', $dateRange)->count(),
            'avg_assignment_duration' => AssetAssignment::whereNotNull('returned_at')
                ->whereBetween('assigned_at', $dateRange)
                ->select(DB::raw('AVG(TIMESTAMPDIFF(DAY, assigned_at, returned_at)) as avg_days'))
                ->first()->avg_days ?? 0,
        ];
    }

    private function getMaintenanceAnalytics($dateRange)
    {
        return [
            'total_maintenance' => MaintenanceRecord::whereBetween('created_at', $dateRange)->count(),
            'pending_maintenance' => MaintenanceRecord::where('status', 'pending')->whereBetween('created_at', $dateRange)->count(),
            'completed_maintenance' => MaintenanceRecord::where('status', 'completed')->whereBetween('created_at', $dateRange)->count(),
            'maintenance_cost' => MaintenanceRecord::whereBetween('created_at', $dateRange)->sum('cost'),
        ];
    }

    private function getRecentAssignments($dateRange, $limit = 10)
    {
        return AssetAssignment::with(['asset', 'user'])
            ->whereBetween('assigned_at', $dateRange)
            ->latest()
            ->limit($limit)
            ->get();
    }

    private function getTopModels($dateRange, $limit = 5)
    {
        return Asset::with('model')
            ->whereBetween('created_at', $dateRange)
            ->select('model_id', DB::raw('count(*) as asset_count'))
            ->groupBy('model_id')
            ->orderByDesc('asset_count')
            ->limit($limit)
            ->get()
            ->map(function ($item) {
                return [
                    'model_name' => $item->model->name ?? 'Unknown',
                    'asset_count' => $item->asset_count,
                ];
            });
    }

    /**
     * License Report Methods
     */
    private function getLicenseSummary($dateRange)
    {
        return [
            'total_licenses' => License::whereBetween('created_at', $dateRange)->count(),
            'active_licenses' => License::where('status', true)->whereBetween('created_at', $dateRange)->count(),
            'expired_licenses' => License::where('expiration_date', '<', now())->whereBetween('created_at', $dateRange)->count(),
            'total_quantity' => License::whereBetween('created_at', $dateRange)->sum('total_qty'),
            'available_quantity' => License::whereBetween('created_at', $dateRange)->sum('available_qty'),
            'utilization_rate' => License::whereBetween('created_at', $dateRange)
                ->select(DB::raw('ROUND((SUM(total_qty - available_qty) / SUM(total_qty)) * 100, 2) as rate'))
                ->first()->rate ?? 0,
        ];
    }

    private function getLicenseStatusDistribution($dateRange)
    {
        $total = License::whereBetween('created_at', $dateRange)->count();
        $active = License::where('status', true)->whereBetween('created_at', $dateRange)->count();
        $expired = License::where('expiration_date', '<', now())->whereBetween('created_at', $dateRange)->count();

        return [
            'active' => $active,
            'inactive' => $total - $active,
            'expired' => $expired,
            'expiring_soon' => License::where('expiration_date', '>', now())
                ->where('expiration_date', '<=', now()->addDays(30))
                ->whereBetween('created_at', $dateRange)
                ->count(),
        ];
    }

    private function getManufacturerBreakdown($dateRange)
    {
        return License::whereBetween('created_at', $dateRange)
            ->select('manufacturer', DB::raw('count(*) as count'), DB::raw('SUM(total_qty) as total_quantity'))
            ->groupBy('manufacturer')
            ->orderByDesc('count')
            ->get();
    }

    private function getExpirationAnalytics($dateRange)
    {
        return [
            'expiring_this_month' => License::whereBetween('expiration_date', [now(), now()->addMonth()])
                ->whereBetween('created_at', $dateRange)
                ->count(),
            'expired_licenses' => License::where('expiration_date', '<', now())
                ->whereBetween('created_at', $dateRange)
                ->count(),
            'next_expiration' => License::where('expiration_date', '>', now())
                ->whereBetween('created_at', $dateRange)
                ->orderBy('expiration_date')
                ->value('expiration_date'),
        ];
    }

    private function getLowStockLicenses($dateRange)
    {
        return License::whereBetween('created_at', $dateRange)
            ->whereRaw('available_qty <= min_qty')
            ->where('available_qty', '>', 0)
            ->get();
    }

    private function getRecentlyAddedLicenses($dateRange, $limit = 10)
    {
        return License::whereBetween('created_at', $dateRange)
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * User Activity Report Methods
     */
    private function getUserStatistics($dateRange)
    {
        return [
            'total_users' => User::whereBetween('created_at', $dateRange)->count(),
            'active_users' => User::where('status', true)->whereBetween('created_at', $dateRange)->count(),
            'users_with_assets' => User::has('assets')->whereBetween('created_at', $dateRange)->count(),
            'new_users_this_period' => User::whereBetween('created_at', $dateRange)->count(),
        ];
    }

    private function getAssetAssignmentsByUser($dateRange)
    {
        return User::withCount(['assets' => function ($query) use ($dateRange) {
            $query->whereBetween('assets.assigned_at', $dateRange);
        }])
            ->has('assets')
            ->whereBetween('created_at', $dateRange)
            ->orderByDesc('assets_count')
            ->limit(10)
            ->get()
            ->map(function ($user) {
                return [
                    'user_name' => $user->name,
                    'asset_count' => $user->assets_count,
                    'department' => $user->department,
                ];
            });
    }

    private function getRecentUserActivities($dateRange, $limit = 15)
    {
        // This would typically come from your activity log
        // For now, we'll return recent asset assignments
        return AssetAssignment::with(['user', 'asset'])
            ->whereBetween('assigned_at', $dateRange)
            ->latest()
            ->limit($limit)
            ->get()
            ->map(function ($assignment) {
                return [
                    'user_name' => $assignment->user->name,
                    'asset_name' => $assignment->asset->name,
                    'action' => 'Asset Assigned',
                    'timestamp' => $assignment->assigned_at,
                ];
            });
    }

    private function getDepartmentBreakdown($dateRange)
    {
        return User::whereBetween('created_at', $dateRange)
            ->whereNotNull('department')
            ->select('department', DB::raw('count(*) as user_count'))
            ->groupBy('department')
            ->orderByDesc('user_count')
            ->get();
    }

    /**
     * Stationary Report Methods
     */
    private function getStationarySummary($dateRange)
    {
        return [
            'total_items' => StationaryItem::whereBetween('created_at', $dateRange)->count(),
            'active_items' => StationaryItem::where('status', true)->whereBetween('created_at', $dateRange)->count(),
            'total_stock_value' => StationaryItem::whereBetween('created_at', $dateRange)
                ->select(DB::raw('SUM(quantity * unit_price) as total_value'))
                ->first()->total_value ?? 0,
            'low_stock_items' => StationaryItem::where('quantity', '<=', DB::raw('min_quantity'))
                ->whereBetween('created_at', $dateRange)
                ->count(),
        ];
    }

    private function getStockAnalytics($dateRange)
    {
        return StationaryItem::whereBetween('created_at', $dateRange)
            ->select(
                DB::raw('SUM(quantity) as total_quantity'),
                DB::raw('COUNT(*) as total_items'),
                DB::raw('AVG(quantity) as avg_quantity')
            )
            ->first();
    }

    private function getMovementAnalytics($dateRange)
    {
        // This would query your stationary_item_movements table
        return [
            'total_movements' => 0, // Replace with actual query
            'in_stock_movements' => 0,
            'out_stock_movements' => 0,
        ];
    }

    private function getLowStockStationaryItems($dateRange)
    {
        return StationaryItem::where('quantity', '<=', DB::raw('min_quantity'))
            ->whereBetween('created_at', $dateRange)
            ->where('quantity', '>', 0)
            ->get();
    }

    private function getTopMovingItems($dateRange, $limit = 10)
    {
        return StationaryItem::whereBetween('created_at', $dateRange)
            ->orderByDesc('quantity')
            ->limit($limit)
            ->get();
    }

    /**
     * Dashboard Report Methods
     */
    private function getDashboardOverview($dateRange)
    {
        return [
            'total_assets' => Asset::whereBetween('created_at', $dateRange)->count(),
            'total_licenses' => License::whereBetween('created_at', $dateRange)->count(),
            'total_users' => User::whereBetween('created_at', $dateRange)->count(),
            'total_stationary_items' => StationaryItem::whereBetween('created_at', $dateRange)->count(),
            'total_assignments' => AssetAssignment::whereBetween('assigned_at', $dateRange)->count(),
            'total_maintenance' => MaintenanceRecord::whereBetween('created_at', $dateRange)->count(),
        ];
    }

    private function getAssetMetrics($dateRange)
    {
        return $this->getAssetSummary($dateRange);
    }

    private function getLicenseMetrics($dateRange)
    {
        return $this->getLicenseSummary($dateRange);
    }

    private function getUserMetrics($dateRange)
    {
        return $this->getUserStatistics($dateRange);
    }

    private function getStationaryMetrics($dateRange)
    {
        return $this->getStationarySummary($dateRange);
    }

    private function getSystemAlerts($dateRange)
    {
        return [
            'expiring_licenses' => License::whereBetween('expiration_date', [now(), now()->addDays(30)])
                ->whereBetween('created_at', $dateRange)
                ->count(),
            'low_stock_licenses' => License::whereRaw('available_qty <= min_qty')
                ->where('available_qty', '>', 0)
                ->whereBetween('created_at', $dateRange)
                ->count(),
            'maintenance_assets' => Asset::where('status', 'maintenance')
                ->whereBetween('created_at', $dateRange)
                ->count(),
            'low_stock_stationary' => StationaryItem::where('quantity', '<=', DB::raw('min_quantity'))
                ->where('quantity', '>', 0)
                ->whereBetween('created_at', $dateRange)
                ->count(),
        ];
    }

    /**
     * Utility Methods
     */
    private function getReportTypes()
    {
        return [
            ['value' => 'asset', 'label' => 'Asset Management Report'],
            ['value' => 'license', 'label' => 'Software License Report'],
            ['value' => 'user_activity', 'label' => 'User Activity Report'],
            ['value' => 'stationary', 'label' => 'Stationary Items Report'],
            ['value' => 'dashboard', 'label' => 'Comprehensive Dashboard Report'],
        ];
    }

    private function getDateRanges()
    {
        return [
            ['value' => 'today', 'label' => 'Today'],
            ['value' => 'yesterday', 'label' => 'Yesterday'],
            ['value' => 'last_7_days', 'label' => 'Last 7 Days'],
            ['value' => 'last_30_days', 'label' => 'Last 30 Days'],
            ['value' => 'this_month', 'label' => 'This Month'],
            ['value' => 'last_month', 'label' => 'Last Month'],
            ['value' => 'this_quarter', 'label' => 'This Quarter'],
            ['value' => 'this_year', 'label' => 'This Year'],
        ];
    }

    private function getDateRange($range)
    {
        return match ($range) {
            'today' => [now()->startOfDay(), now()->endOfDay()],
            'yesterday' => [now()->subDay()->startOfDay(), now()->subDay()->endOfDay()],
            'last_7_days' => [now()->subDays(7)->startOfDay(), now()->endOfDay()],
            'last_30_days' => [now()->subDays(30)->startOfDay(), now()->endOfDay()],
            'this_month' => [now()->startOfMonth(), now()->endOfMonth()],
            'last_month' => [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()],
            'this_quarter' => [now()->startOfQuarter(), now()->endOfQuarter()],
            'this_year' => [now()->startOfYear(), now()->endOfYear()],
            default => [now()->subDays(30)->startOfDay(), now()->endOfDay()],
        };
    }

    private function getPeriodLabel($range)
    {
        return collect($this->getDateRanges())->firstWhere('value', $range)['label'] ?? 'Custom Range';
    }

    private function exportReport($report, $filename)
    {
        // Implement export functionality (PDF, Excel, etc.)
        // For now, return JSON response
        return response()->json([
            'report' => $report,
            'exported_at' => now()->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
