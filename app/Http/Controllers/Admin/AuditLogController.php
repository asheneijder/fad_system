<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Inertia\Inertia;
use Spatie\Activitylog\Models\Activity;

class AuditLogController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Activity::with('causer')
            ->latest();

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhere('subject_type', 'like', "%{$search}%")
                    ->orWhere('log_name', 'like', "%{$search}%")
                    ->orWhere('subject_id', 'like', "%{$search}%")
                    ->orWhereHas('causer', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        // Date range filter
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('created_at', [
                Carbon::parse($request->start_date)->startOfDay(),
                Carbon::parse($request->end_date)->endOfDay(),
            ]);
        }

        // Action type filter
        if ($request->filled('action')) {
            $query->where('description', $request->action);
        }

        // Model type filter
        if ($request->filled('model')) {
            $query->where('subject_type', $request->model);
        }

        $logs = $query->paginate($request->per_page ?? 15)->withQueryString();

        // Get statistics
        $stats = [
            'total' => Activity::count(),
            'created' => Activity::where('description', 'created')->count(),
            'updated' => Activity::where('description', 'updated')->count(),
            'deleted' => Activity::where('description', 'deleted')->count(),
            'today' => Activity::whereDate('created_at', today())->count(),
        ];

        // Get unique models for filter
        $models = Activity::select('subject_type')
            ->distinct()
            ->whereNotNull('subject_type')
            ->pluck('subject_type')
            ->map(fn ($model) => [
                'label' => class_basename($model),
                'value' => $model,
            ])
            ->values();

        return Inertia::render('Admin/AuditLog/Index', [
            'logs' => $logs,
            'filters' => $request->only(['search', 'start_date', 'end_date', 'action', 'model']),
            'stats' => $stats,
            'models' => $models,
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $activity = Activity::with('causer', 'subject')->findOrFail($id);

        return Inertia::render('Admin/AuditLog/Show', [
            'log' => $activity,
        ]);
    }

    /**
     * Export audit logs to CSV
     */
    public function export(Request $request)
    {
        $query = Activity::with('causer')->latest();

        // Apply same filters as index
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhere('subject_type', 'like', "%{$search}%")
                    ->orWhere('subject_id', 'like', "%{$search}%")
                    ->orWhereHas('causer', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('created_at', [
                Carbon::parse($request->start_date)->startOfDay(),
                Carbon::parse($request->end_date)->endOfDay(),
            ]);
        }

        if ($request->filled('action')) {
            $query->where('description', $request->action);
        }

        if ($request->filled('model')) {
            $query->where('subject_type', $request->model);
        }

        $logs = $query->get();

        $filename = 'audit-logs-'.now()->format('Y-m-d-His').'.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($logs) {
            $file = fopen('php://output', 'w');

            // Add BOM for UTF-8
            fwrite($file, "\xEF\xBB\xBF");

            // Add CSV headers
            fputcsv($file, [
                'ID',
                'Action',
                'Model',
                'Model ID',
                'Log Name',
                'User Name',
                'User Email',
                'User ID',
                'Properties (JSON)',
                'IP Address',
                'User Agent',
                'Created At',
                'Updated At',
            ]);

            // Add data rows
            foreach ($logs as $log) {
                fputcsv($file, [
                    $log->id,
                    ucfirst($log->description ?? 'N/A'),
                    $log->subject_type ?? 'N/A',
                    $log->subject_id ?? 'N/A',
                    $log->log_name ?? 'default',
                    $log->causer->name ?? 'System',
                    $log->causer->email ?? 'N/A',
                    $log->causer_id ?? 'N/A',
                    json_encode($log->properties ?? []),
                    $log->properties['ip_address'] ?? 'N/A',
                    $log->properties['user_agent'] ?? 'N/A',
                    $log->created_at->format('Y-m-d H:i:s'),
                    $log->updated_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    /**
     * Get audit log statistics
     */
    public function statistics()
    {
        $stats = [
            'total' => Activity::count(),
            'today' => Activity::whereDate('created_at', today())->count(),
            'this_week' => Activity::whereBetween('created_at', [
                now()->startOfWeek(),
                now()->endOfWeek(),
            ])->count(),
            'this_month' => Activity::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
            'by_action' => [
                'created' => Activity::where('description', 'created')->count(),
                'updated' => Activity::where('description', 'updated')->count(),
                'deleted' => Activity::where('description', 'deleted')->count(),
            ],
            'by_model' => Activity::select('subject_type', \DB::raw('count(*) as count'))
                ->whereNotNull('subject_type')
                ->groupBy('subject_type')
                ->get()
                ->map(fn ($item) => [
                    'model' => class_basename($item->subject_type),
                    'count' => $item->count,
                ]),
            'top_users' => Activity::select('causer_id', \DB::raw('count(*) as count'))
                ->whereNotNull('causer_id')
                ->groupBy('causer_id')
                ->with('causer:id,name,email')
                ->orderByDesc('count')
                ->limit(10)
                ->get()
                ->map(fn ($item) => [
                    'user' => $item->causer->name ?? 'Unknown',
                    'email' => $item->causer->email ?? 'N/A',
                    'count' => $item->count,
                ]),
        ];

        return response()->json($stats);
    }

    /**
     * Delete old audit logs (cleanup)
     */
    public function cleanup(Request $request)
    {
        $request->validate([
            'days' => 'required|integer|min:30',
        ]);

        $date = now()->subDays($request->days);
        $deleted = Activity::where('created_at', '<', $date)->delete();

        return back()->with('success', "Deleted {$deleted} audit log(s) older than {$request->days} days.");
    }

    /**
     * Delete a specific audit log
     */
    public function destroy(string $id)
    {
        $activity = Activity::findOrFail($id);
        $activity->delete();

        return redirect()->route('admin.audit-logs.index')
            ->with('success', 'Audit log deleted successfully.');
    }
}
