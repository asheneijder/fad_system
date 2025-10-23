<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TravelClaim;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ManageClaimRequestController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->query('search');
        $status = $request->query('status');
        $type = $request->query('type');
        $page = $request->query('page', 1);
        $perPage = $request->query('per_page', 10);

        $user = Auth::user();

        // Get all claim types with their counts
        $claimTypes = $this->getClaimTypesWithCounts($user);

        // Base query for travel claims (you'll need to extend this for other claim types)
        $claimsQuery = TravelClaim::with(['user', 'approver']);

        if (! $user->hasRole('system-admin')) {
            $claimsQuery->where('approver_id', $user->id);
        }

        $claims = $claimsQuery
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('purpose', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            })
            ->when($status && $status !== 'all', function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->when($type && $type !== 'all', function ($query) use ($type) {
                if ($type === 'travel') {
                    // This is already travel claims
                }
                // Add other claim type filters here when you have other models
            })
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page)
            ->withQueryString();

        // Transform claims data for unified display
        $transformedClaims = $claims->through(function ($claim) {
            return $this->transformClaimForDisplay($claim);
        });

        // Statistics
        $statistics = $this->getStatistics($user);

        // Status options for filter
        $statusOptions = [
            ['label' => 'All Status', 'value' => 'all'],
            ['label' => 'Draft', 'value' => 'draft'],
            ['label' => 'Pending Approval', 'value' => 'submitted'],
            ['label' => 'Approved', 'value' => 'approved'],
            ['label' => 'Rejected', 'value' => 'rejected'],
        ];

        // Claim type options
        $typeOptions = [
            ['label' => 'All Types', 'value' => 'all'],
            ['label' => 'Travel Claim', 'value' => 'travel'],
            ['label' => 'Daily Allowance', 'value' => 'daily'],
            ['label' => 'Accommodation', 'value' => 'accommodation'],
            ['label' => 'Transportation', 'value' => 'transportation'],
        ];

        return Inertia::render('Admin/ManageClaimRequest/Index', [
            'claims' => $transformedClaims,
            'filters' => $request->only(['search', 'status', 'type']),
            'statistics' => $statistics,
            'claimTypes' => $claimTypes,
            'statusOptions' => $statusOptions,
            'typeOptions' => $typeOptions,
            'userRole' => $user->hasRole('system-admin') ? 'system-admin' : 'approver',
        ]);
    }

    /**
     * Get claim types with their counts
     */
    private function getClaimTypesWithCounts($user)
    {
        $travelQuery = TravelClaim::query();

        if (! $user->hasRole('system-admin')) {
            $travelQuery->where('approver_id', $user->id);
        }

        return [
            [
                'id' => 'travel',
                'name' => 'Travel Claim',
                'description' => 'Claim for travel expenses including fuel, tolls, and transportation',
                'icon' => 'pi pi-car',
                'color' => 'blue-500',
                'bgColor' => 'blue-100',
                'total' => $travelQuery->count(),
                'pending' => $travelQuery->clone()->where('status', 'submitted')->count(),
            ],
            [
                'id' => 'daily',
                'name' => 'Daily Allowance',
                'description' => 'Claim for daily meal allowances',
                'icon' => 'pi pi-wallet',
                'color' => 'green-500',
                'bgColor' => 'green-100',
                'total' => 0, // Placeholder - update when you have DailyAllowance model
                'pending' => 0,
            ],
            [
                'id' => 'accommodation',
                'name' => 'Accommodation Claim',
                'description' => 'Claim for hotel accommodation',
                'icon' => 'pi pi-building',
                'color' => 'purple-500',
                'bgColor' => 'purple-100',
                'total' => 0, // Placeholder - update when you have AccommodationClaim model
                'pending' => 0,
            ],
            [
                'id' => 'transportation',
                'name' => 'Transportation Claim',
                'description' => 'Claim for Grab, taxi, MRT, bus, etc.',
                'icon' => 'pi pi-map-marker',
                'color' => 'orange-500',
                'bgColor' => 'orange-100',
                'total' => 0, // Placeholder - update when you have TransportationClaim model
                'pending' => 0,
            ],
        ];
    }

    /**
     * Transform claim for unified display
     */
    private function transformClaimForDisplay($claim)
    {
        $baseData = [
            'id' => $claim->id,
            'type' => 'travel',
            'type_display' => 'Travel Claim',
            'user' => $claim->user,
            'approver' => $claim->approver,
            'status' => $claim->status,
            'purpose' => $claim->purpose,
            'amount' => $claim->total_cost,
            'submitted_at' => $claim->submitted_at,
            'created_at' => $claim->created_at,
            'vehicle_type' => $claim->vehicle_type,
            'total_distance' => $claim->total_distance,
        ];

        // Add type-specific data
        switch ($claim->getTable()) {
            case 'travel_claims':
                $baseData['details'] = [
                    'vehicle_type' => $claim->vehicle_type,
                    'distance' => $claim->total_distance.' km',
                    'rate' => 'RM '.$claim->rate_per_km.'/km',
                ];
                break;
                // Add cases for other claim types when you have them
        }

        return $baseData;
    }

    /**
     * Get statistics
     */
    private function getStatistics($user)
    {
        $travelQuery = TravelClaim::query();

        if (! $user->hasRole('system-admin')) {
            $travelQuery->where('approver_id', $user->id);
        }

        return [
            'total' => $travelQuery->count(),
            'pending' => $travelQuery->clone()->where('status', 'submitted')->count(),
            'approved' => $travelQuery->clone()->where('status', 'approved')->count(),
            'rejected' => $travelQuery->clone()->where('status', 'rejected')->count(),
            'draft' => $travelQuery->clone()->where('status', 'draft')->count(),
        ];
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        abort(404);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        abort(404);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $user = Auth::user();

        // For now, we only handle travel claims
        // You'll need to extend this to detect claim type
        $claim = TravelClaim::with(['user', 'approver', 'media'])->findOrFail($id);

        // Authorization
        if (! $user->hasRole('system-admin') && $claim->approver_id !== $user->id) {
            abort(403, 'You are not authorized to view this claim.');
        }

        return Inertia::render('Admin/ManageClaimRequest/Show', [
            'claim' => $this->transformClaimForDisplay($claim),
            'claimType' => 'travel',
            'userRole' => $user->hasRole('system-admin') ? 'system-admin' : 'approver',
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        abort(404);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $user = Auth::user();

        // For now, we only handle travel claims
        $claim = TravelClaim::findOrFail($id);

        // Authorization
        if (! $user->hasRole('system-admin') && $claim->approver_id !== $user->id) {
            abort(403, 'You are not authorized to process this claim.');
        }

        $validated = $request->validate([
            'action' => 'required|in:approve,reject',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validated['action'] === 'approve') {
            $claim->update([
                'status' => 'approved',
                'approved_at' => now(),
                'approver_id' => $user->id,
            ]);

            $message = 'Claim approved successfully';
        } else {
            $request->validate([
                'notes' => 'required|string|max:500',
            ]);

            $claim->update([
                'status' => 'rejected',
                'rejection_reason' => $validated['notes'],
                'rejected_at' => now(),
                'approver_id' => $user->id,
            ]);

            $message = 'Claim rejected successfully';
        }

        return redirect()->route('manage.claim-request.index')
            ->with('success', $message);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        abort(404);
    }

    /**
     * Bulk actions for claims
     */
    public function bulkAction(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'action' => 'required|in:approve,reject',
            'claim_ids' => 'required|array|min:1',
            'claim_ids.*' => 'exists:travel_claims,id',
            'notes' => 'nullable|string|max:500',
        ]);

        // Get claims with authorization check
        $claimsQuery = TravelClaim::whereIn('id', $validated['claim_ids']);

        if (! $user->hasRole('system-admin')) {
            $claimsQuery->where('approver_id', $user->id);
        }

        $claims = $claimsQuery->get();

        if ($claims->count() !== count($validated['claim_ids'])) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to process some of the selected claims.',
            ], 403);
        }

        foreach ($claims as $claim) {
            if ($validated['action'] === 'approve') {
                $claim->update([
                    'status' => 'approved',
                    'approved_at' => now(),
                    'approver_id' => $user->id,
                ]);
            } else {
                if (empty($validated['notes'])) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Rejection notes are required.',
                    ], 422);
                }

                $claim->update([
                    'status' => 'rejected',
                    'rejection_reason' => $validated['notes'],
                    'rejected_at' => now(),
                    'approver_id' => $user->id,
                ]);
            }
        }

        $message = $validated['action'] === 'approve'
            ? count($claims).' claim(s) approved successfully'
            : count($claims).' claim(s) rejected successfully';

        return response()->json([
            'success' => true,
            'message' => $message,
        ]);
    }
}
