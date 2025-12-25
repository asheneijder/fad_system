<?php

namespace App\Http\Controllers\Admin;

use App\Exports\AssetReportExport;
use App\Exports\DashboardReportExport;
use App\Exports\LicenseReportExport;
use App\Exports\StationaryReportExport;
use App\Exports\UserActivityReportExport;
use App\Http\Controllers\Controller;
use App\Models\AccommodationClaim;
use App\Models\Asset;
use App\Models\Category;
use App\Models\DailyAllowance;
use App\Models\License;
use App\Models\StationaryItem;
use App\Models\TransportationClaim;
use App\Models\TravelClaim;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    /**
     * Display main reports page
     */
    public function index()
    {
        return Inertia::render('Admin/Reports/Index', [
            'reportTypes' => $this->getReportTypes(),
            'dateRanges' => $this->getDateRanges(),
            'quickStats' => $this->getQuickStats(),
            'recentActivity' => $this->getRecentActivity(),
        ]);
    }

    /**
     * Asset Management Report
     */
    public function assetReport(Request $request)
    {
        \Log::info('Asset Report Request:', $request->all());

        // Start with a basic query
        $query = Asset::with(['category', 'model', 'user']);

        \Log::info('Base query count: ' . $query->count());

        // Apply filters one by one and log the count after each


        if ($request->category) {
            $query->when($request->category, function ($q, $category) {
                $q->whereHas('category', function ($query) use ($category) {
                    $query->where('name', $category);
                });
            });
            \Log::info('After category filter count: ' . $query->count());
        }

        if ($request->status) {
            $query->when($request->status, function ($q, $status) {
                $q->where('status', $status);
            });
            \Log::info('After status filter count: ' . $query->count());
        }

        if ($request->search) {
            $query->when($request->search, function ($q, $search) {
                $q->where(function ($query) use ($search) {
                    $query->where('asset_name', 'like', "%{$search}%")
                        ->orWhere('asset_tag_no', 'like', "%{$search}%")
                        ->orWhere('serial_no', 'like', "%{$search}%");
                });
            });
            \Log::info('After search filter count: ' . $query->count());
        }

        $query->orderBy('category_type_id')->orderBy('asset_tag_no');

        $assets = $query->get();

        \Log::info('Final assets count: ' . $assets->count());

        if ($request->boolean('export')) {
            return Excel::download(
                new AssetReportExport($query->get()),
                'fixed-asset-listing-' . date('Y-m-d') . '.xlsx'
            );
        }

        // Get all categories for filter dropdown
        $allCategories = Category::where('type', 'asset')
            ->orderBy('name')
            ->get()
            ->pluck('name');

        $summary = $this->getEnhancedAssetSummary($assets);
        $chartData = $this->getEnhancedAssetChartData($assets);
        $categoryBreakdown = $this->getCategoryBreakdown($assets);

        return Inertia::render('Admin/Reports/AssetReport', [
            'assets' => $assets,
            'summary' => $summary,
            'chartData' => $chartData,
            'categoryBreakdown' => $categoryBreakdown,
            'allCategories' => $allCategories,
            'statusOptions' => ['active', 'assigned', 'available', 'maintenance', 'retired'],
            'filters' => $request->only(['category', 'status', 'search']),
        ]);
    }

    /**
     * Enhanced Asset Summary with Financial Metrics
     */
    private function getEnhancedAssetSummary($assets)
    {
        $fullyDepreciated = $assets->filter(fn($asset) => $asset->is_fully_depreciated);
        $depreciatingSoon = $assets->filter(
            fn($asset) => $asset->remaining_life_percentage <= 20 && !$asset->is_fully_depreciated
        );
        $needsSighting = $assets->filter(fn($asset) => $asset->needs_sighting);

        $totalPurchaseCost = $assets->sum('purchase_cost');
        $totalDepreciation = $assets->sum('depreciation_cost');
        $totalCurrentValue = $assets->sum('current_value');

        return [
            'total_assets' => $assets->count(),
            'total_purchase_cost' => number_format($totalPurchaseCost, 2),
            'total_depreciation' => number_format($totalDepreciation, 2),
            'total_current_value' => number_format($totalCurrentValue, 2),
            'assigned_assets' => $assets->whereNotNull('assigned_to')->count(),
            'unassigned_assets' => $assets->whereNull('assigned_to')->count(),
            'fully_depreciated' => $fullyDepreciated->count(),
            'depreciating_soon' => $depreciatingSoon->count(),
            'needs_sighting' => $needsSighting->count(),
            'avg_asset_value' => number_format($assets->avg('current_value') ?? 0, 2),
            'utilization_rate' => $assets->count() > 0 ?
                round(($assets->whereNotNull('assigned_to')->count() / $assets->count()) * 100, 1) : 0,
        ];
    }

    /**
     * Enhanced Chart Data
     */
    private function getEnhancedAssetChartData($assets)
    {
        $statusData = $assets->groupBy('status')->map->count();
        $categoryData = $assets->groupBy('category.name')->map->count();

        // Financial breakdown
        $financialData = [
            'Purchase Cost' => (float) $assets->sum('purchase_cost'),
            'Accumulated Depreciation' => (float) $assets->sum('depreciation_cost'),
            'Current Value' => (float) $assets->sum('current_value'),
        ];

        // Depreciation status
        $depreciationStatus = [
            'Fully Depreciated' => $assets->where('is_fully_depreciated', true)->count(),
            'Depreciating' => $assets->where('is_fully_depreciated', false)->count(),
        ];

        return [
            'status_chart' => [
                'labels' => $statusData->keys()->map(fn($status) => ucfirst($status))->toArray(),
                'series' => $statusData->values()->toArray(),
            ],
            'category_chart' => [
                'labels' => $categoryData->keys()->toArray(),
                'series' => $categoryData->values()->toArray(),
            ],
            'financial_chart' => [
                'labels' => array_keys($financialData),
                'series' => array_values($financialData),
            ],
            'depreciation_chart' => [
                'labels' => array_keys($depreciationStatus),
                'series' => array_values($depreciationStatus),
            ],
        ];
    }

    /**
     * Category Breakdown for Summary
     */
    private function getCategoryBreakdown($assets)
    {
        return $assets->groupBy('category.name')->map(function ($categoryAssets, $categoryName) {
            $totalValue = $categoryAssets->sum('current_value');
            $totalCost = $categoryAssets->sum('purchase_cost');

            return [
                'name' => $categoryName,
                'count' => $categoryAssets->count(),
                'total_value' => number_format($totalValue, 2),
                'total_cost' => number_format($totalCost, 2),
                'depreciation' => number_format($totalCost - $totalValue, 2),
                'assigned_count' => $categoryAssets->whereNotNull('assigned_to')->count(),
            ];
        })->sortByDesc('count')->values();
    }

    /**
     * License Management Report
     */
    public function licenseReport(Request $request)
    {
        $query = License::when($request->date_range, function ($q) use ($request) {
            $this->applyDateFilter($q, $request->date_range, 'created_at');
        });

        if ($request->boolean('export')) {
            return Excel::download(
                new LicenseReportExport($query->get()),
                'license-report-' . date('Y-m-d') . '.xlsx'
            );
        }

        $licenses = $query->get();
        $summary = $this->getLicenseSummary($licenses);
        $chartData = $this->getLicenseChartData($licenses);

        return Inertia::render('Admin/Reports/LicenseReport', [
            'licenses' => $licenses,
            'summary' => $summary,
            'chartData' => $chartData,
            'filters' => $request->only(['date_range']),
        ]);
    }

    /**
     * Stationary Items Report
     */
    public function stationaryReport(Request $request)
    {
        $query = StationaryItem::with([
            'movements',
            'requestItemDetails.requestItem.user:id,name,department,email',
            'requestItemDetails.requestItem.approvedBy:id,name',
        ])
            ->when($request->date_range, function ($q) use ($request) {
                $this->applyDateFilter($q, $request->date_range, 'created_at');
            });

        if ($request->boolean('export')) {
            return Excel::download(
                new StationaryReportExport($query->get(), $request->date_range),
                'stationary-report-' . date('Y-m-d') . '.xlsx'
            );
        }

        $stationaryItems = $query->get();

        $hotItems = $stationaryItems->filter(function ($item) {
            return $item->current_stock <= $item->min_stock || $item->current_stock <= 5;
        })->values();

        $usageStats = $this->getStationaryUsageStats($stationaryItems);
        $summary = $this->getStationarySummary($stationaryItems);
        $chartData = $this->getStationaryChartData($stationaryItems);

        return Inertia::render('Admin/Reports/StationaryReport', [
            'stationaryItems' => $stationaryItems,
            'hotItems' => $hotItems,
            'usageStats' => $usageStats,
            'summary' => $summary,
            'chartData' => $chartData,
            'filters' => $request->only(['date_range']),
        ]);
    }

    /**
     * User Activity Report
     */
    public function userActivityReport(Request $request)
    {
        $query = User::with(['roles'])
            ->withCount(['assets'])
            ->when($request->date_range, function ($q) use ($request) {
                $this->applyDateFilter($q, $request->date_range, 'created_at');
            });

        if ($request->boolean('export')) {
            return Excel::download(
                new UserActivityReportExport($query->get()),
                'user-activity-report-' . date('Y-m-d') . '.xlsx'
            );
        }

        $users = $query->get();
        $summary = $this->getUserSummary($users);
        $chartData = $this->getUserChartData($users);

        return Inertia::render('Admin/Reports/UserActivityReport', [
            'users' => $users,
            'summary' => $summary,
            'chartData' => $chartData,
            'filters' => $request->only(['date_range']),
        ]);
    }

    /**
     * Dashboard Report
     */
    public function dashboardReport(Request $request)
    {
        $summary = $this->getDashboardSummary();

        if ($request->boolean('export')) {
            return Excel::download(
                new DashboardReportExport($summary),
                'dashboard-report-' . date('Y-m-d') . '.xlsx'
            );
        }

        $chartData = $this->getDashboardChartData($request->date_range);

        return Inertia::render('Admin/Reports/DashboardReport', [
            'summary' => $summary,
            'chartData' => $chartData,
            'filters' => $request->only(['date_range']),
        ]);
    }

    /**
     * Helper Methods
     */
    private function getReportTypes()
    {
        return [
            ['value' => 'asset', 'label' => 'Asset Management', 'icon' => 'pi pi-desktop', 'description' => 'Asset statistics, assignments, and maintenance analytics', 'color' => 'blue'],
            ['value' => 'license', 'label' => 'Software License', 'icon' => 'pi pi-key', 'description' => 'License utilization, expirations, and manufacturer breakdown', 'color' => 'green'],
            // ['value' => 'user_activity', 'label' => 'User Activity', 'icon' => 'pi pi-users', 'description' => 'User statistics, assignments, and department analytics', 'color' => 'orange'],
            ['value' => 'stationary', 'label' => 'Stationary Items', 'icon' => 'pi pi-shopping-cart', 'description' => 'Stock levels, movements, and inventory analytics', 'color' => 'purple'],
            // ['value' => 'dashboard', 'label' => 'Dashboard Overview', 'icon' => 'pi pi-chart-bar', 'description' => 'Complete system overview with all metrics', 'color' => 'indigo'],
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

    private function getQuickStats()
    {
        return [
            'total_assets' => Asset::count(),
            'total_licenses' => License::count(),
            'total_users' => User::where('status', 'active')->count(),
            'low_stock_items' => StationaryItem::whereColumn('current_stock', '<=', 'min_stock')->count(),
            'pending_claims' => TravelClaim::where('status', 'pending')->count() +
                TransportationClaim::where('status', 'pending')->count() +
                DailyAllowance::where('status', 'pending')->count() +
                AccommodationClaim::where('status', 'pending')->count(),
            'total_asset_value' => number_format(Asset::sum('current_value')),
            'assets_assigned' => Asset::whereNotNull('assigned_to')->count(),
            'licenses_expiring' => License::where('expiration_date', '<=', now()->addDays(30))->count(),
        ];
    }

    private function getRecentActivity()
    {
        return [
            'recent_assets' => Asset::with('user')->latest()->take(5)->get(),
            'recent_licenses' => License::latest()->take(5)->get(),
            'low_stock_items' => StationaryItem::whereColumn('current_stock', '<=', 'min_stock')->take(5)->get(),
        ];
    }

    private function applyDateFilter($query, $dateRange, $dateField = 'created_at')
    {
        return match ($dateRange) {
            'today' => $query->whereDate($dateField, today()),
            'yesterday' => $query->whereDate($dateField, today()->subDay()),
            'last_7_days' => $query->where($dateField, '>=', now()->subDays(7)),
            'last_30_days' => $query->where($dateField, '>=', now()->subDays(30)),
            'this_month' => $query->whereMonth($dateField, now()->month)->whereYear($dateField, now()->year),
            'last_month' => $query->whereMonth($dateField, now()->subMonth()->month)->whereYear($dateField, now()->subMonth()->year),
            'this_quarter' => $query->whereBetween($dateField, [now()->startOfQuarter(), now()->endOfQuarter()]),
            'this_year' => $query->whereYear($dateField, now()->year),
            default => $query,
        };
    }

    /**
     * Enhanced asset summary with depreciation data
     */
    private function getAssetSummary($assets)
    {
        $fullyDepreciated = $assets->filter(function ($asset) {
            return $asset->is_fully_depreciated;
        });

        $depreciatingSoon = $assets->filter(function ($asset) {
            return $asset->remaining_life_percentage <= 20 && !$asset->is_fully_depreciated;
        });

        return [
            'total' => $assets->count(),
            'total_value' => number_format($assets->sum('current_value'), 2),
            'total_purchase_cost' => number_format($assets->sum('purchase_cost'), 2),
            'total_depreciation' => number_format($assets->sum('depreciation_cost'), 2),
            'assigned' => $assets->whereNotNull('assigned_to')->count(),
            'unassigned' => $assets->whereNull('assigned_to')->count(),
            'average_value' => number_format($assets->avg('current_value') ?? 0, 2),
            'fully_depreciated' => $fullyDepreciated->count(),
            'depreciating_soon' => $depreciatingSoon->count(),
            'by_status' => $assets->groupBy('status')->map->count(),
            'by_category' => $assets->groupBy('category.name')->map->count(),
        ];
    }

    private function getAssetChartData($assets)
    {
        $statusData = $assets->groupBy('status')->map->count();

        return [
            'status_chart' => [
                'labels' => $statusData->keys()->toArray(),
                'series' => $statusData->values()->toArray(),
            ],
            'category_chart' => [
                'labels' => ['Assigned', 'Unassigned'],
                'series' => [
                    $assets->whereNotNull('assigned_to')->count(),
                    $assets->whereNull('assigned_to')->count(),
                ],
            ],
        ];
    }

    private function getLicenseSummary($licenses)
    {
        return [
            'total' => $licenses->count(),
            'expiring_soon' => $licenses->where('expiration_date', '<=', now()->addDays(30))->count(),
            'expired' => $licenses->where('expiration_date', '<', now())->count(),
            'total_quantity' => $licenses->sum('total_qty'),
            'available_quantity' => $licenses->sum('available_qty'),
            'utilization_rate' => $licenses->sum('total_qty') > 0 ?
                round((1 - ($licenses->sum('available_qty') / $licenses->sum('total_qty'))) * 100, 2) : 0,
            'by_status' => $licenses->groupBy('status')->map->count(),
            'by_manufacturer' => $licenses->groupBy('manufacturer')->map->count(),
        ];
    }

    private function getLicenseChartData($licenses)
    {
        $statusData = $licenses->groupBy('status')->map->count();
        $manufacturerData = $licenses->groupBy('manufacturer')->map->count();

        return [
            'status_chart' => [
                'labels' => $statusData->keys()->toArray(),
                'series' => $statusData->values()->toArray(),
            ],
            'manufacturer_chart' => [
                'labels' => $manufacturerData->keys()->toArray(),
                'series' => $manufacturerData->values()->toArray(),
            ],
        ];
    }

    /**
     * Get stationary summary statistics
     */
    private function getStationarySummary($stationaryItems)
    {
        $totalValue = $stationaryItems->sum(function ($item) {
            return $item->current_stock * $item->cost_price;
        });

        // Count completed request details
        $completedRequestsCount = 0;
        foreach ($stationaryItems as $item) {
            $completedRequestsCount += $item->requestItemDetails->where('requestItem.status', 'completed')->count();
        }

        return [
            'total' => $stationaryItems->count(),
            'low_stock' => $stationaryItems->where('current_stock', '<=', $stationaryItems->first()->min_stock ?? 0)->count(),
            'out_of_stock' => $stationaryItems->where('current_stock', 0)->count(),
            'total_value' => number_format($totalValue, 2),
            'average_cost' => number_format($stationaryItems->avg('cost_price') ?? 0, 2),
            'completed_requests' => $completedRequestsCount,
            'by_category' => $stationaryItems->groupBy('category')->map->count(),
            'by_status' => $stationaryItems->groupBy('status')->map->count(),
        ];
    }

    /**
     * Get chart data for stationary items
     */
    private function getStationaryChartData($stationaryItems)
    {
        $categoryData = $stationaryItems->groupBy('category')->map->count();
        $stockStatusData = [
            'In Stock' => $stationaryItems->where('current_stock', '>', 0)
                ->filter(function ($item) {
                    return $item->current_stock > $item->min_stock;
                })->count(),
            'Low Stock' => $stationaryItems->where('current_stock', '>', 0)
                ->filter(function ($item) {
                    return $item->current_stock <= $item->min_stock;
                })->count(),
            'Out of Stock' => $stationaryItems->where('current_stock', 0)->count(),
        ];

        return [
            'category_chart' => [
                'labels' => $categoryData->keys()->toArray(),
                'series' => $categoryData->values()->toArray(),
            ],
            'stock_chart' => [
                'labels' => array_keys($stockStatusData),
                'series' => array_values($stockStatusData),
            ],
        ];
    }

    private function getUserSummary($users)
    {
        return [
            'total' => $users->count(),
            'active' => $users->where('status', 'active')->count(),
            'inactive' => $users->where('status', 'inactive')->count(),
            'with_assets' => $users->where('assets_count', '>', 0)->count(),
            'average_assets' => round($users->avg('assets_count'), 1),
            'by_department' => $users->groupBy('department')->map->count(),
            'by_role' => $users->flatMap->roles->groupBy('name')->map->count(),
        ];
    }

    private function getUserChartData($users)
    {
        $departmentData = $users->groupBy('department')->map->count();
        $statusData = $users->groupBy('status')->map->count();

        return [
            'department_chart' => [
                'labels' => $departmentData->keys()->toArray(),
                'series' => $departmentData->values()->toArray(),
            ],
            'status_chart' => [
                'labels' => $statusData->keys()->toArray(),
                'series' => $statusData->values()->toArray(),
            ],
        ];
    }

    /**
     * Get stationary usage statistics from completed requests
     */
    private function getStationaryUsageStats($stationaryItems)
    {
        $usageStats = [];

        foreach ($stationaryItems as $item) {
            foreach ($item->requestItemDetails as $detail) {
                // Only count completed requests
                if ($detail->requestItem && $detail->requestItem->status === 'completed') {
                    $user = $detail->requestItem->user;
                    if ($user) {
                        $userKey = $user->id;

                        if (!isset($usageStats[$userKey])) {
                            $usageStats[$userKey] = [
                                'user' => $user->name,
                                'department' => $user->department ?? 'Unknown Department',
                                'email' => $user->email,
                                'items_used' => 0,
                                'total_used' => 0,
                                'last_used' => null,
                            ];
                        }

                        $usageStats[$userKey]['items_used']++;
                        $usageStats[$userKey]['total_used'] += $detail->final_quantity;

                        $requestDate = $detail->requestItem->created_at;
                        if (!$usageStats[$userKey]['last_used'] || $requestDate > $usageStats[$userKey]['last_used']) {
                            $usageStats[$userKey]['last_used'] = $requestDate->format('M d, Y');
                        }
                    }
                }
            }
        }

        // Sort by total used descending and take top 5
        usort($usageStats, function ($a, $b) {
            return $b['total_used'] - $a['total_used'];
        });

        return array_slice($usageStats, 0, 5);
    }

    private function getDashboardSummary()
    {
        $totalAssets = Asset::count();
        $assignedAssets = Asset::whereNotNull('assigned_to')->count();
        $totalLicenses = License::count();
        $expiringLicenses = License::where('expiration_date', '<=', now()->addDays(30))->count();
        $totalUsers = User::where('status', 'active')->count();
        $lowStockItems = StationaryItem::whereColumn('current_stock', '<=', 'min_stock')->count();

        return [
            'assets' => [
                'total' => $totalAssets,
                'assigned' => $assignedAssets,
                'unassigned' => $totalAssets - $assignedAssets,
                'assigned_rate' => $totalAssets > 0 ? round(($assignedAssets / $totalAssets) * 100, 1) : 0,
                'total_value' => number_format(Asset::sum('current_value'), 2),
            ],
            'licenses' => [
                'total' => $totalLicenses,
                'expiring' => $expiringLicenses,
                'expired' => License::where('expiration_date', '<', now())->count(),
                'utilization' => License::sum('total_qty') > 0 ?
                    round((1 - (License::sum('available_qty') / License::sum('total_qty'))) * 100, 1) : 0,
            ],
            'users' => [
                'total' => $totalUsers,
                'with_assets' => User::has('assets')->count(),
                'active' => $totalUsers,
                'inactive' => User::where('status', 'inactive')->count(),
            ],
            'stationary' => [
                'total' => StationaryItem::count(),
                'low_stock' => $lowStockItems,
                'out_of_stock' => StationaryItem::where('current_stock', 0)->count(),
                'alert_rate' => StationaryItem::count() > 0 ? round(($lowStockItems / StationaryItem::count()) * 100, 1) : 0,
            ],
            'claims' => [
                'pending' => TravelClaim::where('status', 'pending')->count() +
                    TransportationClaim::where('status', 'pending')->count() +
                    DailyAllowance::where('status', 'pending')->count() +
                    AccommodationClaim::where('status', 'pending')->count(),
                'approved' => TravelClaim::where('status', 'approved')->count() +
                    TransportationClaim::where('status', 'approved')->count() +
                    DailyAllowance::where('status', 'approved')->count() +
                    AccommodationClaim::where('status', 'approved')->count(),
                'total' => TravelClaim::count() + TransportationClaim::count() + DailyAllowance::count() + AccommodationClaim::count(),
            ],
        ];
    }

    private function getDashboardChartData($dateRange = 'last_30_days')
    {
        $startDate = match ($dateRange) {
            'today' => now()->startOfDay(),
            'yesterday' => now()->subDay()->startOfDay(),
            'last_7_days' => now()->subDays(7)->startOfDay(),
            'last_30_days' => now()->subDays(30)->startOfDay(),
            'this_month' => now()->startOfMonth(),
            'last_month' => now()->subMonth()->startOfMonth(),
            'this_quarter' => now()->startOfQuarter(),
            'this_year' => now()->startOfYear(),
            default => now()->subDays(30)->startOfDay(),
        };

        // Asset acquisitions
        $assetData = Asset::where('purchase_date', '>=', $startDate)
            ->groupBy(DB::raw('DATE(purchase_date)'))
            ->select(DB::raw('DATE(purchase_date) as date'), DB::raw('COUNT(*) as count'))
            ->orderBy('date')
            ->get();

        // User registrations
        $userData = User::where('created_at', '>=', $startDate)
            ->groupBy(DB::raw('DATE(created_at)'))
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
            ->orderBy('date')
            ->get();

        return [
            'asset_trend' => [
                'labels' => $assetData->pluck('date')->toArray(),
                'series' => [$assetData->pluck('count')->toArray()],
            ],
            'user_trend' => [
                'labels' => $userData->pluck('date')->toArray(),
                'series' => [$userData->pluck('count')->toArray()],
            ],
            'stationary_usage' => [
                'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                'series' => [[65, 59, 80, 81, 56, 55]],
            ],
        ];
    }
}
