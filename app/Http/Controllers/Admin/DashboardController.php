<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RequestItem;
use App\Models\RequestItemDetail;
use App\Models\StationaryItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        // Check if user is admin or FAD approver
        if (!auth()->user()->isAdmin() && !auth()->user()->isFadApprover()) {
            abort(403, 'Unauthorized access.');
        }
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;

        // Quick Stats
        $stats = [
            'total_requests' => RequestItem::count(),
            'pending_requests' => RequestItem::where('status', 'pending')->count(),
            'approved_requests' => RequestItem::where('status', 'approved')->count(),
            'completed_requests' => RequestItem::where('status', 'completed')->count(),
            'total_users' => User::count(),
            'total_items' => StationaryItem::count(),
            'low_stock_items' => StationaryItem::where('current_stock', '<=', DB::raw('min_stock'))->count(),
            'this_month_requests' => RequestItem::whereYear('created_at', $currentYear)
                ->whereMonth('created_at', $currentMonth)
                ->count(),
        ];

        // Monthly Request Trends (Last 6 months)
        $monthlyTrends = RequestItem::selectRaw('
            YEAR(created_at) as year,
            MONTH(created_at) as month,
            COUNT(*) as total_requests,
            SUM(CASE WHEN status = "approved" THEN 1 ELSE 0 END) as approved_requests,
            SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending_requests
        ')
            ->where('created_at', '>=', Carbon::now()->subMonths(6))
            ->groupBy('year', 'month')
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'month' => Carbon::createFromDate($item->year, $item->month, 1)->format('M Y'),
                    'total' => $item->total_requests,
                    'approved' => $item->approved_requests,
                    'pending' => $item->pending_requests,
                ];
            })
            ->reverse()
            ->values();

        // Top Requested Items (This Month)
        $topRequestedItems = RequestItemDetail::selectRaw('
            stationary_items.name,
            stationary_items.category,
            SUM(request_item_details.quantity) as total_requested,
            COUNT(DISTINCT request_item_details.request_item_id) as request_count
        ')
            ->join('stationary_items', 'request_item_details.stationary_item_id', '=', 'stationary_items.id')
            ->join('request_items', 'request_item_details.request_item_id', '=', 'request_items.id')
            ->whereYear('request_items.created_at', $currentYear)
            ->whereMonth('request_items.created_at', $currentMonth)
            ->groupBy('stationary_items.id', 'stationary_items.name', 'stationary_items.category')
            ->orderBy('total_requested', 'desc')
            ->limit(10)
            ->get();

        // Top Requestors (This Month) - FIXED: Specify table for status column
        $topRequestors = RequestItem::selectRaw('
            users.name,
            users.email,
            users.department,
            COUNT(*) as request_count,
            SUM(CASE WHEN request_items.status = "approved" THEN 1 ELSE 0 END) as approved_count
        ')
            ->join('users', 'request_items.user_id', '=', 'users.id')
            ->whereYear('request_items.created_at', $currentYear)
            ->whereMonth('request_items.created_at', $currentMonth)
            ->groupBy('users.id', 'users.name', 'users.email', 'users.department')
            ->orderBy('request_count', 'desc')
            ->limit(8)
            ->get();

        // Request Status Distribution
        $statusDistribution = RequestItem::selectRaw('
            status,
            COUNT(*) as count,
            ROUND((COUNT(*) * 100.0 / (SELECT COUNT(*) FROM request_items)), 1) as percentage
        ')
            ->groupBy('status')
            ->get();

        $specifiedItems = [
            'A4 Paper', // Check if this exact name exists in DB
            'BULLET STAPLES MAX NO-10', // From your DB dump
            'PAPER CLIP SMALL', // You'll need to check the exact name
            'FABER CASTLE CLICK',
            'FABER CASTLE CLICK PEN BLACK', // You'll need to check the exact name
            'FABER CASTLE CLICK PEN BLUE', // You'll need to check the exact name
            'HILIGHTER (RED)', // From your DB dump - note this is RED not GREEN
            'HILIGHTER (YELLOW)', // From your DB dump - note this is YELLOW not PINK
            'POST IT', // You'll need to check the exact name
            'FAIL PUTIH', // You'll need to check the exact name
            'PETTY CASH VOUCHER', // You'll need to check the exact name
            'LETTER HEAD ARTB', // You'll need to check the exact name
        ];

        // Low Stock Alert Items
        $lowStockItems = StationaryItem::where('status', true)
            ->whereIn('name', $specifiedItems)
            ->orderBy('current_stock', 'asc')
            ->limit(10)
            ->get(['id', 'name', 'current_stock', 'min_stock', 'unit']);

        // dd($lowStockItems);

        // Recent Pending Requests
        $recentPendingRequests = RequestItem::with(['user:id,name,email', 'items.stationaryItem:id,name'])
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Department-wise Requests
        $departmentRequests = RequestItem::selectRaw('
            COALESCE(users.department, "Not Specified") as department,
            COUNT(*) as request_count
        ')
            ->join('users', 'request_items.user_id', '=', 'users.id')
            ->whereYear('request_items.created_at', $currentYear)
            ->groupBy('users.department')
            ->orderBy('request_count', 'desc')
            ->get();

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'monthlyTrends' => $monthlyTrends,
            'topRequestedItems' => $topRequestedItems,
            'topRequestors' => $topRequestors,
            'statusDistribution' => $statusDistribution,
            'lowStockItems' => $lowStockItems,
            'recentPendingRequests' => $recentPendingRequests,
            'departmentRequests' => $departmentRequests,
        ]);
    }

    public function getChartData(Request $request)
    {
        // Check if user is admin or FAD approver
        if (!auth()->user()->isAdmin() && !auth()->user()->isFadApprover()) {
            abort(403, 'Unauthorized access.');
        }

        $period = $request->get('period', 'monthly'); // monthly, weekly, yearly

        if ($period === 'weekly') {
            $data = $this->getWeeklyData();
        } elseif ($period === 'yearly') {
            $data = $this->getYearlyData();
        } else {
            $data = $this->getMonthlyData();
        }

        return response()->json($data);
    }

    private function getMonthlyData()
    {
        return RequestItem::selectRaw('
            YEAR(created_at) as year,
            MONTH(created_at) as month,
            COUNT(*) as total,
            SUM(CASE WHEN status = "approved" THEN 1 ELSE 0 END) as approved,
            SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed
        ')
            ->where('created_at', '>=', Carbon::now()->subMonths(11))
            ->groupBy('year', 'month')
            ->orderBy('year', 'asc')
            ->orderBy('month', 'asc')
            ->get()
            ->map(function ($item) {
                return [
                    'label' => Carbon::createFromDate($item->year, $item->month, 1)->format('M Y'),
                    'total' => $item->total,
                    'approved' => $item->approved,
                    'pending' => $item->pending,
                    'completed' => $item->completed,
                ];
            });
    }

    private function getWeeklyData()
    {
        return RequestItem::selectRaw('
            YEAR(created_at) as year,
            WEEK(created_at) as week,
            COUNT(*) as total,
            SUM(CASE WHEN status = "approved" THEN 1 ELSE 0 END) as approved
        ')
            ->where('created_at', '>=', Carbon::now()->subWeeks(8))
            ->groupBy('year', 'week')
            ->orderBy('year', 'asc')
            ->orderBy('week', 'asc')
            ->get()
            ->map(function ($item) {
                return [
                    'label' => 'Week ' . $item->week . ' ' . $item->year,
                    'total' => $item->total,
                    'approved' => $item->approved,
                ];
            });
    }

    private function getYearlyData()
    {
        return RequestItem::selectRaw('
            YEAR(created_at) as year,
            COUNT(*) as total,
            SUM(CASE WHEN status = "approved" THEN 1 ELSE 0 END) as approved
        ')
            ->where('created_at', '>=', Carbon::now()->subYears(5))
            ->groupBy('year')
            ->orderBy('year', 'asc')
            ->get()
            ->map(function ($item) {
                return [
                    'label' => $item->year,
                    'total' => $item->total,
                    'approved' => $item->approved,
                ];
            });
    }
}
