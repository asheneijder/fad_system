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
     * Display a listing of the audit logs.
     */
    public function index(Request $request)
    {
        $query = Activity::with(['causer', 'subject'])
            ->latest();

        // Search filter
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('log_name', 'like', "%{$search}%")
                  ->orWhere('event', 'like', "%{$search}%")
                  ->orWhereHas('causer', function ($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                  })
                  ->orWhereHas('subject', function ($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        // Log name filter
        if ($request->has('log_name') && $request->log_name) {
            $query->where('log_name', $request->log_name);
        }

        // Event filter
        if ($request->has('event') && $request->event) {
            $query->where('event', $request->event);
        }

        // Date range filter
        if ($request->has('date_from') && $request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $logs = $query->paginate($request->per_page ?? 25);

        // Get unique log names and events for filters
        $logNames = Activity::distinct()->pluck('log_name')->filter()->values();
        $events = Activity::distinct()->pluck('event')->filter()->values();

        return Inertia::render('Admin/AuditLogs/Index', [
            'logs' => $logs,
            'filters' => [
                'search' => $request->search ?? '',
                'log_name' => $request->log_name ?? '',
                'event' => $request->event ?? '',
                'date_from' => $request->date_from ?? '',
                'date_to' => $request->date_to ?? '',
                'per_page' => $request->per_page ?? 25,
            ],
            'logNames' => $logNames,
            'events' => $events,
        ]);
    }

    /**
     * Display the specified audit log.
     */
    public function show($id)
    {
        $log = Activity::with(['causer', 'subject'])
            ->findOrFail($id);

        return Inertia::render('Admin/AuditLogs/Show', [
            'log' => $log,
        ]);
    }

    /**
     * Export audit logs to CSV
     */
    public function export(Request $request)
    {
        $query = Activity::with(['causer', 'subject'])
            ->latest();

        // Apply filters same as index
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('log_name', 'like', "%{$search}%")
                  ->orWhere('event', 'like', "%{$search}%")
                  ->orWhereHas('causer', function ($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->has('log_name') && $request->log_name) {
            $query->where('log_name', $request->log_name);
        }

        if ($request->has('event') && $request->event) {
            $query->where('event', $request->event);
        }

        if ($request->has('date_from') && $request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $logs = $query->get();

        $fileName = 'audit-logs-' . Carbon::now()->format('Y-m-d-H-i-s') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename={$fileName}",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($logs) {
            $file = fopen('php://output', 'w');
            
            // Add CSV headers
            fputcsv($file, [
                'ID',
                'Description',
                'Event',
                'Log Name',
                'Causer',
                'Causer Email',
                'Subject Type',
                'Subject ID',
                'Properties',
                'IP Address',
                'User Agent',
                'Created At',
            ]);

            // Add data rows
            foreach ($logs as $log) {
                fputcsv($file, [
                    $log->id,
                    $log->description,
                    $log->event,
                    $log->log_name,
                    $log->causer ? $log->causer->name : 'System',
                    $log->causer ? $log->causer->email : 'N/A',
                    $log->subject_type ? class_basename($log->subject_type) : 'N/A',
                    $log->subject_id ?? 'N/A',
                    json_encode($log->properties->toArray()),
                    $log->properties->get('ip_address') ?? 'N/A',
                    $log->properties->get('user_agent') ?? 'N/A',
                    $log->created_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    /**
     * Clear old audit logs
     */
    public function clearOldLogs(Request $request)
    {
        $request->validate([
            'days' => 'required|integer|min:1|max:3650', // Max 10 years
        ]);

        $cutoffDate = Carbon::now()->subDays($request->days);
        
        $deletedCount = Activity::where('created_at', '<', $cutoffDate)->delete();

        return redirect()->back()->with('success', "Successfully cleared {$deletedCount} audit logs older than {$request->days} days.");
    }
}