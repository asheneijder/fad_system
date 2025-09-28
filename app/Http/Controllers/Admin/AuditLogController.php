<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Models\Activity as ActivityModel;

class AuditLogController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $logs = ActivityModel::with('causer')
            ->latest()
            ->when($request->filled('search'), fn ($query) => $query->whereAny(['description', 'subject_type'], 'like', "%{$request->search}%")
                ->orWhereHas('causer', fn ($q) => $q->whereAny(['name', 'email'], 'like', "%{$request->search}%")
                )
            )
            ->when($request->filled('start_date') && $request->filled('end_date'), fn ($query) => $query->whereBetween('created_at', [
                \Carbon\Carbon::parse($request->start_date)->startOfDay(),
                \Carbon\Carbon::parse($request->end_date)->endOfDay(),
            ])
            )
            ->paginate(15)
            ->withQueryString();

        // dd($logs);

        return Inertia::render('Admin/AuditLog/Index', [
            'logs' => $logs,
            'filters' => $request->only(['search', 'start_date', 'end_date']),
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
        $activity = Activity::with('causer')->findOrFail($id);

        return Inertia::render('Admin/AuditLog/Show', [
            'log' => $activity->load('causer'),
        ]);

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
