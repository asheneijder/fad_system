<?php

namespace App\Http\Controllers\Admin;

use App\Exports\AuditLogsExport;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
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
                'User Name',
                'User Email',
                'Description',
                'Changes',
                'IP Address',
                'User Agent',
                'Date & Time',
            ]);

            // Add data rows
            foreach ($logs as $log) {
                $changes = $this->formatChangesForExport($log);

                fputcsv($file, [
                    $log->id,
                    ucfirst($log->description),
                    class_basename($log->subject_type ?? 'N/A'),
                    $log->subject_id ?? 'N/A',
                    $log->causer->name ?? 'System',
                    $log->causer->email ?? 'N/A',
                    $log->log_name ?? 'N/A',
                    $changes,
                    $log->properties['ip_address'] ?? 'N/A',
                    $log->properties['user_agent'] ?? 'N/A',
                    $log->created_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    /**
     * Export audit logs to Excel
     */
    public function exportExcel(Request $request)
    {
        // Apply filters
        $filters = [
            'search' => $request->search,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'action' => $request->action,
            'model' => $request->model,
        ];

        $filename = 'audit-logs-'.now()->format('Y-m-d-His').'.xlsx';

        return Excel::download(new AuditLogsExport($filters), $filename);
    }

    /**
     * Export audit logs to PDF
     */
    public function exportPdf(Request $request)
    {
        $query = Activity::with('causer')->latest();

        // Apply same filters as index
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhere('subject_type', 'like', "%{$search}%")
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

        $filename = 'audit-logs-'.now()->format('Y-m-d-His').'.pdf';

        // You can use a PDF library like DomPDF or TCPDF here
        // For now, we'll return a simple response
        return response()->json([
            'message' => 'PDF export will be implemented with a PDF library',
            'total_logs' => $logs->count(),
            'filters' => $request->all(),
        ]);
    }

    /**
     * Format changes for export
     */
    private function formatChangesForExport($log): string
    {
        if (! $log->properties) {
            return 'No changes';
        }

        $changes = [];

        if ($log->description === 'updated' && isset($log->properties['attributes'], $log->properties['old'])) {
            foreach ($log->properties['attributes'] as $key => $newValue) {
                $oldValue = $log->properties['old'][$key] ?? 'N/A';
                $changes[] = "{$key}: {$oldValue} → {$newValue}";
            }
        } elseif ($log->description === 'created' && isset($log->properties['attributes'])) {
            foreach ($log->properties['attributes'] as $key => $value) {
                $changes[] = "{$key}: {$value}";
            }
        } elseif ($log->description === 'deleted' && isset($log->properties['old'])) {
            foreach ($log->properties['old'] as $key => $value) {
                $changes[] = "{$key}: {$value}";
            }
        }

        return implode(' | ', $changes) ?: 'No detailed changes';
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
}
