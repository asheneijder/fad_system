<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\Category;
use App\Models\License;
use App\Models\ModelType;
use App\Models\RequestItem;
use App\Models\StationaryItem;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $basicStats = $this->getBasicStats();
        $recentActivities = $this->getRecentActivities();
        $stockAlerts = $this->getStockAlerts();
        $requestStats = $this->getRequestStats();
        $assetStats = $this->getAssetStats();
        $movementTrends = $this->getMovementTrends();

        return Inertia::render('Dashboard', [
            'basicStats' => $basicStats,
            'recentActivities' => $recentActivities,
            'stockAlerts' => $stockAlerts,
            'requestStats' => $requestStats,
            'assetStats' => $assetStats,
            'movementTrends' => $movementTrends,
        ]);
    }

    public function chartData($type = 'requests'): JsonResponse
    {
        $data = [];

        switch ($type) {
            case 'requests':
                $data = $this->getRequestChartData();
                break;
            case 'assets':
                $data = $this->getAssetChartData();
                break;
            case 'stationary':
                $data = $this->getStationaryChartData();
                break;
        }

        return response()->json($data);
    }

    private function getBasicStats(): array
    {
        return [
            'users' => User::count(),
            'assets' => Asset::count(),
            'categories' => Category::count(),
            'models' => ModelType::count(),
            'licenses' => License::count(),
            'stationary_items' => StationaryItem::count(),
            'pending_requests' => RequestItem::where('status', 'pending')->count(),
        ];
    }

    private function getRecentActivities(): array
    {
        return [
            'users' => User::latest()
                ->take(5)
                ->get()
                ->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'job_title' => $user->job_title,
                        'created_at' => $user->created_at,
                    ];
                }),
            'assets' => Asset::with('model') // Changed from 'category' to 'model'
                ->latest()
                ->take(5)
                ->get()
                ->map(function ($asset) {
                    return [
                        'id' => $asset->id,
                        'name' => $asset->name,
                        'asset_tag' => $asset->asset_tag,
                        'created_at' => $asset->created_at,
                    ];
                }),
        ];
    }

    private function getStockAlerts(): array
    {
        return StationaryItem::where(function ($query) {
            $query->where('current_stock', '<=', DB::raw('min_stock'))
                ->orWhere('current_stock', '=', 0);
        })
            ->get()
            ->map(function ($item) {
                $status = $item->current_stock == 0 ? 'out_of_stock' :
                         ($item->current_stock <= $item->min_stock ? 'low_stock' : 'adequate');

                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'current_stock' => $item->current_stock,
                    'min_stock' => $item->min_stock,
                    'status' => $status,
                ];
            })
            ->toArray();
    }

    private function getRequestStats(): array
    {
        $total = RequestItem::count();
        $byStatus = RequestItem::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        return [
            'total' => $total,
            'by_status' => $byStatus,
        ];
    }

    private function getAssetStats(): array
    {
        $total = Asset::count();

        // Get assets by category through model relationship
        $byCategory = Asset::with('model.category')
            ->get()
            ->groupBy(function ($asset) {
                return $asset->model->category->name ?? 'Uncategorized';
            })
            ->map(function ($group) {
                return $group->count();
            })
            ->toArray();

        return [
            'total' => $total,
            'by_category' => $byCategory,
        ];
    }

    private function getMovementTrends(): array
    {
        // Get assets by status
        $assetsByStatus = Asset::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        return [
            'assets_added' => Asset::where('created_at', '>=', now()->subDays(30))->count(),
            'assets_by_status' => $assetsByStatus,
            'total_assets' => Asset::count(),
        ];
    }

    private function getRequestChartData(): array
    {
        $startDate = now()->subDays(30);
        $endDate = now();

        $requests = Request::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('count(*) as count')
        )
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $dates = [];
        $counts = [];
        $currentDate = $startDate->copy();

        while ($currentDate <= $endDate) {
            $dateString = $currentDate->format('Y-m-d');
            $dates[] = $currentDate->format('M j');
            $count = $requests->firstWhere('date', $dateString);
            $counts[] = $count ? $count->count : 0;
            $currentDate->addDay();
        }

        return [
            'labels' => $dates,
            'datasets' => [
                [
                    'label' => 'Requests',
                    'data' => $counts,
                    'fill' => false,
                    'borderColor' => '#3B82F6',
                    'tension' => 0.4,
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                ],
            ],
        ];
    }

    private function getAssetChartData(): array
    {
        $startDate = now()->subDays(30);
        $endDate = now();

        $assets = Asset::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('count(*) as count')
        )
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $dates = [];
        $counts = [];
        $currentDate = $startDate->copy();

        while ($currentDate <= $endDate) {
            $dateString = $currentDate->format('Y-m-d');
            $dates[] = $currentDate->format('M j');
            $count = $assets->firstWhere('date', $dateString);
            $counts[] = $count ? $count->count : 0;
            $currentDate->addDay();
        }

        return [
            'labels' => $dates,
            'datasets' => [
                [
                    'label' => 'Assets Added',
                    'data' => $counts,
                    'fill' => false,
                    'borderColor' => '#10B981',
                    'tension' => 0.4,
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                ],
            ],
        ];
    }

    private function getStationaryChartData(): array
    {
        $startDate = now()->subDays(30);
        $endDate = now();

        $stationary = StationaryItem::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('count(*) as count')
        )
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $dates = [];
        $counts = [];
        $currentDate = $startDate->copy();

        while ($currentDate <= $endDate) {
            $dateString = $currentDate->format('Y-m-d');
            $dates[] = $currentDate->format('M j');
            $count = $stationary->firstWhere('date', $dateString);
            $counts[] = $count ? $count->count : 0;
            $currentDate->addDay();
        }

        return [
            'labels' => $dates,
            'datasets' => [
                [
                    'label' => 'Stationary Items',
                    'data' => $counts,
                    'fill' => false,
                    'borderColor' => '#8B5CF6',
                    'tension' => 0.4,
                    'backgroundColor' => 'rgba(139, 92, 246, 0.1)',
                ],
            ],
        ];
    }

    // Additional helper method for asset status breakdown
    private function getAssetStatusBreakdown(): array
    {
        return Asset::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
    }

    // Additional helper method for license status
    private function getLicenseStats(): array
    {
        return [
            'total' => License::count(),
            'active' => License::active()->count(),
            'expired' => License::expired()->count(),
            'expiring_soon' => License::expiringSoon()->count(),
            'low_stock' => License::where('available_qty', '<=', DB::raw('min_qty'))->count(),
        ];
    }
}
