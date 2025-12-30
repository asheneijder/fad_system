<?php

namespace App\Http\Controllers\Admin;

use App\Exports\AssetsExport;
use App\Http\Controllers\Controller;
use App\Jobs\SendAcknowledgmentReminder;
use App\Jobs\SendAssetAssignmentNotification;
use App\Models\Asset;
use App\Models\AssetAssignment;
use App\Models\Category;
use App\Models\ModelType;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class AssetController extends Controller
{
    public function __construct(
        protected Asset $asset,
        protected User $user,
        protected AssetAssignment $assetAssignment,
        protected ModelType $modelType
    ) {
    }

    public function index(Request $request)
    {
        $search = $request->query('search');
        $status = $request->query('status');
        $category = $request->query('category');
        $location = $request->query('location');
        $perPage = $request->query('per_page', 10);

        $assets = $this->asset->with(['model', 'category', 'user', 'currentAssignment'])
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('asset_name', 'like', "%{$search}%")
                        ->orWhere('asset_tag_no', 'like', "%{$search}%")
                        ->orWhere('serial_no', 'like', "%{$search}%")
                        ->orWhereHas('model', function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%")
                                ->orWhere('brand', 'like', "%{$search}%");
                        })
                        ->orWhereHas('user', function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            })
            ->when($status && $status !== '', function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->when($category && $category !== '', function ($query) use ($category) {
                $query->where('category_type_id', $category);
            })
            ->when($location && $location !== '', function ($query) use ($location) {
                $query->where('location', 'like', "%{$location}%");
            })
            // Sort by asset_tag_no with natural sorting
            ->orderByRaw('LENGTH(asset_tag_no) ASC, asset_tag_no ASC')
            ->paginate($perPage)
            ->withQueryString();

        $categories = Category::where('status', true)->get();
        $users = $this->user->where('status', true)->get(['id', 'name', 'email']);
        $models = $this->modelType->where('status', true)->get();
        $locations = collect([
            'LD',
            'LD STORE',
            'SMD',
            'SMD STORE',
            'FAD',
            'FAD STORE',
            'DD',
            'FA',
            'AGM OFFICE',
            'CD',
            'CD STORE',
            'HCD',
            'HCD STORE',
            'CEO',
            'CBO',
            'CCGO',
            'DCMT',
            'DCMT STORE',
            'RU',
            'RU STORE',
            'CU',
            'CU STORE',
            'SU',
            'SU STORE',
            'DAMU',
            'DAMU STORE',
            'GTSU',
            'GTSU STORE',
            'PANTRY LEVEL 31',
            'PANTRY LEVEL 30',
            'PANTRY LEVEL 12',
            'PANTRY LEVEL 14',
            'RECEPTION COUNTER',
            'KCP',
            'SERVER ROOM',
            'SOFTWARE',
            'IT SERVER ROOM LEVEL 5',
            'IT SERVER CYBERJAYA',
            'MEETING ROOM LEVEL 30',
            'MEETING ROOM LEVEL 31',
            'STRONG ROOM LEVEL 14',
            'MEETING ROOM LEVEL 12',
            'MEETING ROOM LEVEL 14',
            'LIBRARY LEVEL 14',
            'DISCUSSION ROOM LEVEL 30',
            'MULTIPURPOSE ROOM LEVEL 30',
            'OTHERS',
        ]);

        $secondaryLocations = collect([
            'VISTA TOWER',
            'WAR',
            'KCP',
            'NULL',
        ]);

        $statistics = [
            'total' => $this->asset->count(),
            'active' => $this->asset->active()->count(),
            'available' => $this->asset->available()->count(),
            'assigned' => $this->asset->assigned()->count(),
            'maintenance' => $this->asset->maintenance()->count(),
            'retired' => $this->asset->retired()->count(),
            'total_value' => $this->asset->sum('current_value'),
            'needs_sighting' => $this->asset->needsSighting()->count(),
        ];

        return Inertia::render('Admin/Assets/Index', [
            'assets' => $assets,
            'filters' => $request->only(['search', 'status', 'category', 'location']),
            'categories' => $categories,
            'users' => $users,
            'models' => $models,
            'locations' => $locations,
            'secondaryLocations' => $secondaryLocations,
            'statistics' => $statistics,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'asset_name' => 'required|string|max:255',
            'asset_tag_no' => 'required|string|max:100|unique:assets,asset_tag_no',
            'serial_no' => 'nullable|string|max:100|unique:assets,serial_no',
            'model_type_id' => 'required|exists:model_types,id',
            'category_type_id' => 'required|exists:categories,id',
            'status' => 'required|in:active,available,assigned,maintenance,retired',
            'qty' => 'required|integer|min:1',
            'location' => 'required|string|max:255',
            'location_2' => 'nullable|string|max:255',
            'purchase_date' => 'nullable|date',
            'purchase_cost' => 'nullable|numeric|min:0',
            'current_value' => 'nullable|numeric|min:0',
            'estimated_life' => 'nullable|integer|min:0',
            'estimated_life_days' => 'nullable|integer|min:0',
            'fully_depreciated_date' => 'nullable|date|after_or_equal:purchase_date',
            'depreciation_cost' => 'nullable|numeric|min:0',
            'last_sighting_date' => 'nullable|date|before_or_equal:today',
            'notes' => 'nullable|string',
            'image' => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $asset = new Asset($validated);
            $asset->updated_by = Auth::id();

            // Calculate initial values if not provided
            if ($asset->purchase_cost && !$asset->current_value) {
                $asset->current_value = $asset->purchase_cost;
            }

            // Auto-calculate estimated_life_days if not provided but estimated_life is
            if ($asset->estimated_life && !$asset->estimated_life_days) {
                $asset->estimated_life_days = $asset->estimated_life * 365;
            }

            $asset->save();

            // activity()
            //     ->causedBy(Auth::user())
            //     ->performedOn($asset)
            //     ->log('created asset');

            DB::commit();

            return redirect()->route('admin.assets.index')
                ->with('success', 'Asset created successfully.');

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()
                ->with('error', 'Failed to create asset: ' . $e->getMessage());
        }
    }

    public function show(Asset $asset)
    {
        $asset->load([
            'model',
            'category',
            'user',
            'updatedBy',
            'assignments.user',
            'assignments.assignedBy',
            'maintenanceRecords',
        ]);

        return Inertia::render('Admin/Assets/Show', [
            'asset' => $asset,
        ]);
    }

    public function update(Request $request, Asset $asset)
    {
        $validated = $request->validate([
            'asset_name' => 'required|string|max:255',
            'asset_tag_no' => 'required|string|max:100|unique:assets,asset_tag_no,' . $asset->id,
            'serial_no' => 'nullable|string|max:100|unique:assets,serial_no,' . $asset->id,
            'model_type_id' => 'required|exists:model_types,id',
            'category_type_id' => 'required|exists:categories,id',
            'status' => 'required|in:active,available,assigned,maintenance,retired',
            'qty' => 'required|integer|min:1',
            'location' => 'required|string|max:255',
            'location_2' => 'nullable|string|max:255',
            'purchase_date' => 'nullable|date',
            'purchase_cost' => 'nullable|numeric|min:0',
            'current_value' => 'nullable|numeric|min:0',
            'estimated_life' => 'nullable|integer|min:0',
            'estimated_life_days' => 'nullable|integer|min:0',
            'fully_depreciated_date' => 'nullable|date|after_or_equal:purchase_date',
            'depreciation_cost' => 'nullable|numeric|min:0',
            'last_sighting_date' => 'nullable|date|before_or_equal:today',
            'assigned_to' => 'nullable|exists:users,id',
            'notes' => 'nullable|string',
            'image' => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $oldStatus = $asset->status;
            $asset->fill($validated);
            $asset->updated_by = Auth::id();

            // Auto-calculate estimated_life_days if not provided but estimated_life is
            if ($asset->estimated_life && !$asset->estimated_life_days) {
                $asset->estimated_life_days = $asset->estimated_life * 365;
            }

            $asset->save();

            // Handle status change from assigned to available
            if ($oldStatus === 'assigned' && $validated['status'] === 'available') {
                $asset->update([
                    'assigned_to' => null,
                    'assigned_at' => null,
                    'updated_by' => Auth::id(),
                ]);

                // Mark current assignment as returned
                $assignment = $asset->assignments()->active()->first();
                if ($assignment) {
                    $assignment->update([
                        'returned_at' => now(),
                        'condition_returned' => 'Asset status changed to available via edit',
                    ]);
                }
            }

            // activity()
            //     ->causedBy(Auth::user())
            //     ->performedOn($asset)
            //     ->log('updated asset');

            DB::commit();

            return redirect()->route('admin.assets.index')
                ->with('success', 'Asset updated successfully.');

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()
                ->with('error', 'Failed to update asset: ' . $e->getMessage());
        }
    }

    public function destroy(Asset $asset)
    {
        try {
            DB::beginTransaction();

            if ($asset->assignments()->active()->exists()) {
                return redirect()->back()
                    ->with('error', 'Cannot delete asset with active assignments.');
            }

            $assetName = $asset->asset_name;
            $asset->delete();

            // activity()
            //     ->causedBy(Auth::user())
            //     ->log('deleted asset: '.$assetName);

            DB::commit();

            return redirect()->route('admin.assets.index')
                ->with('success', 'Asset deleted successfully.');

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()
                ->with('error', 'Failed to delete asset: ' . $e->getMessage());
        }
    }

    public function listAssetAssign()
    {
        $assetAssignments = AssetAssignment::with([
            'asset',
            'asset.model',
            'asset.category',
            'user',
            'assignedBy'
        ])
            ->orderBy('assigned_at', 'desc')
            ->paginate(20);

        $summary = [
            'total' => AssetAssignment::count(),
            'acknowledged' => AssetAssignment::whereNotNull('acknowledged_at')->count(),
            'pending' => AssetAssignment::whereNull('acknowledged_at')->whereNull('returned_at')->count(),
            'returned' => AssetAssignment::whereNotNull('returned_at')->count(),
        ];

        return Inertia::render('Admin/AssetAssignments/Index', [
            'assetAssignments' => $assetAssignments,
            'summary' => $summary
        ]);
    }

    public function userAcknowledgmentReport()
    {
        $users = User::withCount([
            'assetAssignments as total_assignments',
            'assetAssignments as acknowledged_assignments' => function ($query) {
                $query->whereNotNull('acknowledged_at');
            },
            'assetAssignments as pending_assignments' => function ($query) {
                $query->whereNull('acknowledged_at')->whereNull('returned_at');
            },
            'assetAssignments as returned_assignments' => function ($query) {
                $query->whereNotNull('returned_at');
            }
        ])
            ->has('assetAssignments')
            ->with(['assetAssignments.asset'])
            ->paginate(20);

        return Inertia::render('Admin/AssetAssignments/UserAcknowledgmentReport', [
            'users' => $users
        ]);
    }

    public function sendReminder(Request $request, User $user)
    {
        try {
            // Get user's pending assignments
            $pendingAssignments = $user->pendingAssetAssignments();

            if ($pendingAssignments->isEmpty()) {
                return redirect()->back()->with('error', 'User has no pending assignments to acknowledge.');
            }

            // Dispatch job to send reminder
            SendAcknowledgmentReminder::dispatch($user, $pendingAssignments, auth()->user());

            return redirect()->back()->with('success', "Acknowledgment reminder sent successfully to {$user->name}.");

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to send reminder: ' . $e->getMessage());
        }
    }

    public function updateStatus(Request $request, Asset $asset)
    {
        $validated = $request->validate([
            'status' => 'required|in:active,available,assigned,maintenance,retired',
        ]);

        try {
            DB::beginTransaction();

            $oldStatus = $asset->status;
            $asset->update([
                'status' => $validated['status'],
                'updated_by' => Auth::id(),
            ]);

            if ($oldStatus === 'assigned' && $validated['status'] === 'available') {
                $asset->update([
                    'assigned_to' => null,
                    'assigned_at' => null,
                    'updated_by' => Auth::id(),
                ]);

                $assignment = $asset->assignments()->active()->first();
                if ($assignment) {
                    $assignment->update([
                        'returned_at' => now(),
                        'condition_returned' => 'Asset status changed to available',
                    ]);
                }
            }

            // activity()
            //     ->causedBy(Auth::user())
            //     ->performedOn($asset)
            //     ->log("changed asset status from {$oldStatus} to {$validated['status']}");

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Asset status updated successfully.',
                'asset' => $asset->fresh(['model', 'user']),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to update asset status: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function assignToUser(Request $request)
    {
        $validated = $request->validate([
            'asset_id' => 'required|exists:assets,id',
            'user_id' => 'required|exists:users,id',
            'notes' => 'nullable|string',
            'condition_assigned' => 'required|string|max:500',
            'assigned_at' => 'nullable|date',
        ]);

        try {
            DB::beginTransaction();

            $asset = Asset::findOrFail($validated['asset_id']);
            $user = User::findOrFail($validated['user_id']); // Get the user being assigned

            if (!$asset->canBeAssigned()) {
                return back()->with('error', 'Asset is not available for assignment.');
            }

            $assignment = $asset->assignToUser(
                $validated['user_id'],
                Auth::id(),
                $validated['condition_assigned'],
                $validated['notes'],
                $validated['assigned_at'] ?? null
            );

            $assignment->update([
                'acknowledged_at' => null,
            ]);

            // Dispatch email job to notify the USER
            SendAssetAssignmentNotification::dispatch($asset, $user, $assignment, Auth::user());

            // activity()
            //     ->causedBy(Auth::user())
            //     ->performedOn($asset)
            //     ->withProperties(['assigned_to' => $validated['user_id']])
            //     ->log('assigned asset to user');

            DB::commit();

            return back()->with('success', 'Asset assigned successfully. User has been notified via email.');

        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Failed to assign asset: ' . $e->getMessage());
        }
    }

    public function returnAsset(Request $request, Asset $asset)
    {
        $validated = $request->validate([
            'condition_returned' => 'required|string|max:500',
            'notes' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            if (!$asset->canBeReturned()) {
                return back()->with('error', 'Asset is not currently assigned.');
            }

            $success = $asset->returnFromAssignment(
                $validated['condition_returned'],
                $validated['notes'] ?? null
            );

            if (!$success) {
                return back()->with('error', 'No active assignment found for this asset.');
            }

            // activity()
            //     ->causedBy(Auth::user())
            //     ->performedOn($asset)
            //     ->log('returned asset from user');

            DB::commit();

            return back()->with('success', 'Asset returned successfully.');

        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Failed to return asset: ' . $e->getMessage());
        }
    }

    public function assignmentHistory(Asset $asset)
    {
        $assignments = $asset->assignments()
            ->with(['user', 'assignedBy'])
            ->orderBy('assigned_at', 'desc')
            ->paginate(10);

        return Inertia::render('Admin/Assets/AssignmentHistory', [
            'asset' => $asset->load(['model', 'category', 'user']),
            'assignments' => $assignments,
        ]);
    }

    public function bulkAssign(Request $request)
    {
        $validated = $request->validate([
            'asset_ids' => 'required|array|min:1',
            'asset_ids.*' => 'exists:assets,id',
            'user_id' => 'required|exists:users,id',
            'notes' => 'nullable|string',
            'condition_assigned' => 'required|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $successCount = 0;
            $failedAssets = [];

            foreach ($validated['asset_ids'] as $assetId) {
                $asset = $this->asset->find($assetId);

                if ($asset->canBeAssigned()) {
                    $asset->assignToUser(
                        $validated['user_id'],
                        Auth::id(),
                        $validated['condition_assigned'],
                        $validated['notes']
                    );
                    $successCount++;
                } else {
                    $failedAssets[] = $asset->asset_name;
                }
            }

            DB::commit();

            $message = "Successfully assigned {$successCount} asset(s).";
            if (!empty($failedAssets)) {
                $message .= ' Failed to assign: ' . implode(', ', $failedAssets);
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'success_count' => $successCount,
                'failed_assets' => $failedAssets,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to assign assets: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function bulkUpdateStatus(Request $request)
    {
        $validated = $request->validate([
            'asset_ids' => 'required|array|min:1',
            'asset_ids.*' => 'exists:assets,id',
            'status' => 'required|in:active,available,assigned,maintenance,retired',
        ]);

        try {
            DB::beginTransaction();

            $updatedCount = 0;

            foreach ($validated['asset_ids'] as $assetId) {
                $asset = $this->asset->find($assetId);
                $oldStatus = $asset->status;

                $asset->update([
                    'status' => $validated['status'],
                    'updated_by' => Auth::id(),
                ]);

                if ($oldStatus === 'assigned' && $validated['status'] === 'available') {
                    $asset->update([
                        'assigned_to' => null,
                        'assigned_at' => null,
                        'updated_by' => Auth::id(),
                    ]);

                    $assignment = $asset->assignments()->active()->first();
                    if ($assignment) {
                        $assignment->update([
                            'returned_at' => now(),
                            'condition_returned' => 'Asset status changed to available via bulk update',
                        ]);
                    }
                }

                $updatedCount++;
            }

            DB::commit();

            // Return Inertia response with flash message
            return back()->with('success', "{$updatedCount} asset(s) status updated to {$validated['status']}");

        } catch (\Exception $e) {
            DB::rollBack();

            // Return Inertia response with error message
            return back()->with('error', 'Failed to update assets status: ' . $e->getMessage());
        }
    }

    public function cancelAcknowledgment(AssetAssignment $assignment)
    {
        try {
            // Check if this is the current assignment and update asset status if needed
            if (!$assignment->returned_at) {
                // If this is an active assignment, update the asset status back to available
                $asset = $assignment->asset;
                $asset->update([
                    'status' => 'available',
                    'assigned_to' => null,
                    'assigned_at' => null,
                    'updated_by' => Auth::id(),
                ]);
            }

            // Delete the assignment record
            $assignment->delete();

            return back()->with('success', 'Assignment has been permanently deleted.');

        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete assignment: ' . $e->getMessage());
        }
    }

    public function export(Request $request)
    {
        $assetIds = $request->input('asset_ids', []);

        // Handle both array and string input
        if (is_string($assetIds)) {
            $assetIds = explode(',', $assetIds);
        }

        $filename = 'assets_' . now()->format('Y-m-d') . '.xlsx';

        return Excel::download(new AssetsExport($assetIds), $filename);
    }

    public function exportAssignmentHistory(Asset $asset)
    {
        $assignments = $asset->assignments()
            ->with(['user', 'assignedBy'])
            ->orderBy('assigned_at', 'desc')
            ->get();

        $filename = 'assignment_history_' . $asset->asset_tag_no . '_' . now()->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($assignments, $asset) {
            $file = fopen('php://output', 'w');

            // Add headers
            fputcsv($file, [
                'Asset Name',
                'Asset Tag',
                'Assigned To',
                'User Email',
                'Assigned By',
                'Assignment Date',
                'Return Date',
                'Status',
                'Duration (Days)',
                'Condition Assigned',
                'Condition Returned',
                'Notes',
            ]);

            // Add data
            foreach ($assignments as $assignment) {
                $duration = '—';
                if ($assignment->assigned_at) {
                    $startDate = new \Carbon\Carbon($assignment->assigned_at);
                    $endDate = $assignment->returned_at ? new \Carbon\Carbon($assignment->returned_at) : now();
                    $duration = $startDate->diffInDays($endDate);
                }

                fputcsv($file, [
                    $asset->asset_name,
                    $asset->asset_tag_no,
                    $assignment->user?->name ?? 'Unknown User',
                    $assignment->user?->email ?? '—',
                    $assignment->assignedBy?->name ?? 'Unknown',
                    $assignment->assigned_at ? $assignment->assigned_at->format('Y-m-d H:i:s') : '—',
                    $assignment->returned_at ? $assignment->returned_at->format('Y-m-d H:i:s') : '—',
                    $assignment->returned_at ? 'Returned' : 'Active',
                    $duration,
                    $assignment->condition_assigned ?? '—',
                    $assignment->condition_returned ?? '—',
                    $assignment->notes ?? '—',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
