<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\RequestItem;
use App\Models\StationaryItem;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UserDashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;

        // User-specific stats
        $stats = [
            'my_total_requests' => RequestItem::where('user_id', $user->id)->count(),
            'my_pending_requests' => RequestItem::where('user_id', $user->id)
                ->where('status', 'pending')
                ->count(),
            'my_approved_requests' => RequestItem::where('user_id', $user->id)
                ->where('status', 'approved')
                ->count(),
            'my_completed_requests' => RequestItem::where('user_id', $user->id)
                ->where('status', 'completed')
                ->count(),
            'available_items' => StationaryItem::where('status', true)
                ->where('current_stock', '>', 0)
                ->count(),
        ];

        // User's recent requests
        $recentRequests = RequestItem::with(['items.stationaryItem:id,name'])
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // User's request trends (last 6 months)
        $myRequestTrends = RequestItem::selectRaw('
            YEAR(created_at) as year,
            MONTH(created_at) as month,
            COUNT(*) as total_requests,
            SUM(CASE WHEN status = "approved" THEN 1 ELSE 0 END) as approved_requests
        ')
            ->where('user_id', $user->id)
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
                ];
            })
            ->reverse()
            ->values();

        // Popular items among all users (for suggestions)
        $popularItems = \App\Models\RequestItemDetail::selectRaw('
            stationary_items.name,
            stationary_items.category,
            stationary_items.current_stock,
            COUNT(request_item_details.id) as total_requests
        ')
            ->join('stationary_items', 'request_item_details.stationary_item_id', '=', 'stationary_items.id')
            ->join('request_items', 'request_item_details.request_item_id', '=', 'request_items.id')
            ->where('stationary_items.status', true)
            ->where('stationary_items.current_stock', '>', 0)
            ->whereYear('request_items.created_at', $currentYear)
            ->whereMonth('request_items.created_at', $currentMonth)
            ->groupBy('stationary_items.id', 'stationary_items.name', 'stationary_items.category', 'stationary_items.current_stock')
            ->orderBy('total_requests', 'desc')
            ->limit(6)
            ->get();

        return Inertia::render('User/Dashboard', [
            'stats' => $stats,
            'recentRequests' => $recentRequests,
            'myRequestTrends' => $myRequestTrends,
            'popularItems' => $popularItems,
        ]);
    }
}
