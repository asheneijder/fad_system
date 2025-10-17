<?php

// app/Http/Controllers/Admin/AssetController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetAssignment;
use App\Models\Category;
use App\Models\ModelType;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class AssetController extends Controller
{
    public function __construct(
        protected Asset $asset,
        protected User $user,
        protected AssetAssignment $assetAssignment,
        protected ModelType $modelType
    ) {}

    public function index(Request $request)
    {
        $search = $request->query('search');
        $status = $request->query('status');
        $category = $request->query('category');

        $assets = $this->asset->with(['model.category', 'user', 'currentAssignment'])
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('asset_tag', 'like', "%{$search}%")
                        ->orWhere('serial_number', 'like', "%{$search}%")
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
            ->when($status && $status !== 'all', function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->when($category && $category !== 'all', function ($query) use ($category) {
                $query->whereHas('model.category', function ($q) use ($category) {
                    $q->where('id', $category);
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        $categories = Category::where('type', 'product')->get();
        $users = $this->user->where('status', true)->get(['id', 'name', 'email']);
        $models = $this->modelType->with('category')->where('status', true)->get();

        $statistics = [
            'total' => $this->asset->count(),
            'available' => $this->asset->available()->count(),
            'assigned' => $this->asset->assigned()->count(),
            'maintenance' => $this->asset->maintenance()->count(),
            'retired' => $this->asset->retired()->count(),
            'with_warranty' => $this->asset->withWarranty()->count(),
        ];

        return Inertia::render('Admin/Assets/Index', [
            'assets' => $assets,
            'filters' => $request->only(['search', 'status', 'category']),
            'categories' => $categories,
            'users' => $users,
            'models' => $models,
            'statistics' => $statistics,
        ]);
    }

    // Remove create method since we're using modals
    // public function create()
    // {
    //     $models = $this->modelType->with('category')->where('status', true)->get();
    //     $categories = Category::where('type', 'product')->get();
    //
    //     return Inertia::render('Admin/Assets/Create', [
    //         'models' => $models,
    //         'categories' => $categories,
    //     ]);
    // }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'asset_tag' => 'required|string|max:100|unique:assets,asset_tag',
            'serial_number' => 'nullable|string|max:100|unique:assets,serial_number',
            'model_id' => 'required|exists:model_types,id',
            'status' => 'required|in:available,assigned,maintenance,retired',
            'purchase_date' => 'nullable|date',
            'purchase_cost' => 'nullable|numeric|min:0',
            'warranty_months' => 'nullable|integer|min:0',
            'notes' => 'nullable|string',
            'image' => 'nullable|string|max:500',
            'location' => 'nullable|string|max:255',
        ]);

        try {
            DB::beginTransaction();

            $asset = $this->asset->create($validated);

            activity()
                ->causedBy(Auth::user())
                ->performedOn($asset)
                ->log('created asset');

            DB::commit();

            return redirect()->route('admin.assets.index')
                ->with('success', 'Asset created successfully.');

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()
                ->with('error', 'Failed to create asset: '.$e->getMessage());
        }
    }

    public function show(Asset $asset)
    {
        $asset->load([
            'model.category',
            'user',
            'assignments.user',
            'assignments.assignedBy',
            // 'maintenanceRecords', // Remove this line to fix the error
        ]);

        return Inertia::render('Admin/Assets/Show', [
            'asset' => $asset,
        ]);
    }

    // Remove edit method since we're using modals
    // public function edit(Asset $asset)
    // {
    //     $models = $this->modelType->with('category')->where('status', true)->get();
    //     $categories = Category::where('type', 'product')->get();
    //     $users = $this->user->where('status', true)->get(['id', 'name', 'email']);
    //
    //     $asset->load(['model', 'user']);
    //
    //     return Inertia::render('Admin/Assets/Edit', [
    //         'asset' => $asset,
    //         'models' => $models,
    //         'categories' => $categories,
    //         'users' => $users,
    //     ]);
    // }

    public function update(Request $request, Asset $asset)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'asset_tag' => 'required|string|max:100|unique:assets,asset_tag,'.$asset->id,
            'serial_number' => 'nullable|string|max:100|unique:assets,serial_number,'.$asset->id,
            'model_id' => 'required|exists:model_types,id',
            'status' => 'required|in:available,assigned,maintenance,retired',
            'purchase_date' => 'nullable|date',
            'purchase_cost' => 'nullable|numeric|min:0',
            'warranty_months' => 'nullable|integer|min:0',
            'notes' => 'nullable|string',
            'image' => 'nullable|string|max:500',
            'location' => 'nullable|string|max:255',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        try {
            DB::beginTransaction();

            $oldStatus = $asset->status;
            $asset->update($validated);

            // Handle status change from assigned to available
            if ($oldStatus === 'assigned' && $validated['status'] === 'available') {
                $asset->update(['assigned_to' => null, 'assigned_at' => null]);

                // Mark current assignment as returned
                $assignment = $asset->assignments()->active()->first();
                if ($assignment) {
                    $assignment->update([
                        'returned_at' => now(),
                        'condition_returned' => 'Asset status changed to available via edit',
                    ]);
                }
            }

            activity()
                ->causedBy(Auth::user())
                ->performedOn($asset)
                ->log('updated asset');

            DB::commit();

            return redirect()->route('admin.assets.index')
                ->with('success', 'Asset updated successfully.');

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()
                ->with('error', 'Failed to update asset: '.$e->getMessage());
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

            $assetName = $asset->name;
            $asset->delete();

            activity()
                ->causedBy(Auth::user())
                ->log('deleted asset: '.$assetName);

            DB::commit();

            return redirect()->route('admin.assets.index')
                ->with('success', 'Asset deleted successfully.');

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()
                ->with('error', 'Failed to delete asset: '.$e->getMessage());
        }
    }

    public function updateStatus(Request $request, Asset $asset)
    {
        $validated = $request->validate([
            'status' => 'required|in:available,assigned,maintenance,retired',
        ]);

        try {
            DB::beginTransaction();

            $oldStatus = $asset->status;
            $asset->update($validated);

            if ($oldStatus === 'assigned' && $validated['status'] === 'available') {
                $asset->update(['assigned_to' => null, 'assigned_at' => null]);

                $assignment = $asset->assignments()->active()->first();
                if ($assignment) {
                    $assignment->update([
                        'returned_at' => now(),
                        'condition_returned' => 'Asset status changed to available',
                    ]);
                }
            }

            activity()
                ->causedBy(Auth::user())
                ->performedOn($asset)
                ->log("changed asset status from {$oldStatus} to {$validated['status']}");

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
                'message' => 'Failed to update asset status: '.$e->getMessage(),
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
        ]);

        try {
            DB::beginTransaction();

            $asset = $this->asset->findOrFail($validated['asset_id']);

            if (! $asset->canBeAssigned()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Asset is not available for assignment.',
                ], 422);
            }

            $assignment = $asset->assignToUser(
                $validated['user_id'],
                Auth::id(),
                $validated['condition_assigned'],
                $validated['notes']
            );

            activity()
                ->causedBy(Auth::user())
                ->performedOn($asset)
                ->withProperties(['assigned_to' => $validated['user_id']])
                ->log('assigned asset to user');

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Asset assigned successfully.',
                'assignment' => $assignment->load('user'),
                'asset' => $asset->fresh(['model', 'user']),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to assign asset: '.$e->getMessage(),
            ], 500);
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

            if (! $asset->canBeReturned()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Asset is not currently assigned.',
                ], 422);
            }

            $success = $asset->returnFromAssignment(
                $validated['condition_returned'],
                $validated['notes']
            );

            if (! $success) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active assignment found for this asset.',
                ], 422);
            }

            activity()
                ->causedBy(Auth::user())
                ->performedOn($asset)
                ->log('returned asset from user');

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Asset returned successfully.',
                'asset' => $asset->fresh(['model', 'user']),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to return asset: '.$e->getMessage(),
            ], 500);
        }
    }

    public function assignmentHistory(Asset $asset)
    {
        $assignments = $asset->assignments()
            ->with(['user', 'assignedBy'])
            ->orderBy('assigned_at', 'desc')
            ->paginate(10);

        return Inertia::render('Admin/Assets/AssignmentHistory', [
            'asset' => $asset->load(['model']),
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
                    $failedAssets[] = $asset->name;
                }
            }

            DB::commit();

            $message = "Successfully assigned {$successCount} asset(s).";
            if (! empty($failedAssets)) {
                $message .= ' Failed to assign: '.implode(', ', $failedAssets);
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
                'message' => 'Failed to assign assets: '.$e->getMessage(),
            ], 500);
        }
    }

    public function bulkUpdateStatus(Request $request)
    {
        $validated = $request->validate([
            'asset_ids' => 'required|array|min:1',
            'asset_ids.*' => 'exists:assets,id',
            'status' => 'required|in:available,assigned,maintenance,retired',
        ]);

        try {
            DB::beginTransaction();

            $updatedCount = 0;

            foreach ($validated['asset_ids'] as $assetId) {
                $asset = $this->asset->find($assetId);
                $oldStatus = $asset->status;

                $asset->update(['status' => $validated['status']]);

                if ($oldStatus === 'assigned' && $validated['status'] === 'available') {
                    $asset->update(['assigned_to' => null, 'assigned_at' => null]);

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

            return response()->json([
                'success' => true,
                'message' => "{$updatedCount} asset(s) status updated to {$validated['status']}",
                'updated_count' => $updatedCount,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to update assets status: '.$e->getMessage(),
            ], 500);
        }
    }

    public function export(Request $request)
    {
        $assetIds = $request->input('asset_ids', []);

        $assets = $this->asset->with(['model.category', 'user'])
            ->when(! empty($assetIds), function ($query) use ($assetIds) {
                $query->whereIn('id', $assetIds);
            })
            ->get();

        $filename = 'assets_'.now()->format('Y-m-d').'.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];

        $callback = function () use ($assets) {
            $file = fopen('php://output', 'w');

            // Add headers
            fputcsv($file, [
                'Asset Name',
                'Asset Tag',
                'Serial Number',
                'Model',
                'Category',
                'Status',
                'Assigned To',
                'Purchase Date',
                'Purchase Cost',
                'Warranty Months',
                'Location',
                'Created At',
            ]);

            // Add data
            foreach ($assets as $asset) {
                fputcsv($file, [
                    $asset->name,
                    $asset->asset_tag,
                    $asset->serial_number ?? 'N/A',
                    $asset->model ? $asset->model->name : 'N/A',
                    $asset->model->category->name ?? 'N/A',
                    $asset->status,
                    $asset->user ? $asset->user->name : 'Not Assigned',
                    $asset->purchase_date ? $asset->purchase_date->format('Y-m-d') : 'N/A',
                    $asset->purchase_cost,
                    $asset->warranty_months,
                    $asset->location ?? 'N/A',
                    $asset->created_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
