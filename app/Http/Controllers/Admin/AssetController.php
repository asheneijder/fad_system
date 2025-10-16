<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAssetRequest;
use App\Http\Requests\UpdateAssetRequest;
use App\Models\Asset;
use App\Models\AssetAssignment;
use App\Models\CategoryType;
use App\Models\ModelType;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AssetController extends Controller
{
    public function __construct(
        protected Asset $asset,
        protected User $user,
        protected AssetAssignment $assetAssignment,
        protected ModelType $modelType
    ) {}

    public function index(Request $req)
    {
        $search = $req->query('search');
        $statuses = $req->query('statuses', []);
        $categories = $req->query('categories', []);

        $assets = $this->asset->query()
            ->with([
                'modelType.categoryType',
                'currentAssignment.user', // Use currentAssignment instead of users
            ])
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('asset_name', 'like', "%{$search}%")
                        ->orWhere('asset_tag_no', 'like', "%{$search}%")
                        ->orWhere('serial_no', 'like', "%{$search}%");
                });
            })
            ->when(! empty($statuses), function ($query) use ($statuses) {
                $query->whereIn('status', $statuses);
            })
            ->when(! empty($categories), function ($query) use ($categories) {
                $query->whereHas('modelType.categoryType', function ($q) use ($categories) {
                    $q->whereIn('id', $categories);
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Admin/Asset/Index', [
            'assets' => $assets,
            'filters' => $req->only(['search', 'statuses', 'categories']) + ['page' => $assets->currentPage()],
            'users' => $this->user->select('id', 'name', 'email')->get(),
            'categories' => CategoryType::select('id', 'category_name')->get(),
        ]);
    }

    public function create()
    {
        $categories = CategoryType::with(['modelTypes' => function ($query) {
            $query->select('id', 'model_name', 'category_type_id');
        }])->get(['id', 'category_name']);

        return Inertia::render('Admin/Asset/Create', [
            'categories' => $categories,
        ]);
    }

    public function store(StoreAssetRequest $req)
    {
        $data = $req->validated();

        $asset = $this->asset->create([
            ...$data,
            'updated_by' => auth()->id(),
        ]);

        if ($req->hasFile('image')) {
            $asset->addMediaFromRequest('image')->toMediaCollection('images');
        }

        return to_route('admin.assets.index')->with('success', 'Asset created successfully.');
    }

    public function edit(Asset $asset)
    {
        $asset->load('modelType.categoryType', 'media');

        $categories = CategoryType::with(['modelTypes' => function ($query) {
            $query->select('id', 'model_name', 'category_type_id');
        }])->get(['id', 'category_name']);

        return Inertia::render('Admin/Asset/Edit', [
            'asset' => $asset,
            'categories' => $categories,
        ]);
    }

    public function update(UpdateAssetRequest $request, Asset $asset)
    {
        $validated = $request->validated();

        $asset->update($validated);

        if ($request->hasFile('image')) {
            $asset->clearMediaCollection('images');
            $asset->addMediaFromRequest('image')->toMediaCollection('images');
        }

        return redirect()->route('admin.assets.index')->with('success', 'Asset updated successfully.');
    }

    public function destroy(Asset $asset)
    {
        // Check if asset has active assignments
        if ($asset->assignments()->whereNull('returned_at')->exists()) {
            return redirect()->back()->with('error', 'Cannot delete asset with active assignments.');
        }

        $asset->delete();

        return to_route('admin.assets.index')->with('success', 'Asset deleted successfully.');
    }

    public function show(Asset $asset)
    {
        $asset->load([
            'modelType.categoryType',
            'media',
            'currentAssignment.user',
            'assignments.user',
            'createdBy',
            'updatedBy',
        ]);

        return Inertia::render('Admin/Asset/Show', [
            'asset' => $asset,
        ]);
    }

    public function updateStatus(Request $request, Asset $asset)
    {
        $request->validate([
            'status' => 'required|string|in:available,assigned,active,inactive,damaged,lost,retired,disposed',
            'remarks' => 'nullable|string|max:500',
            'returned_at' => 'nullable|date',
        ]);

        $asset->update([
            'status' => $request->status,
            'updated_by' => auth()->id(),
        ]);

        // If asset is being marked as unavailable, close any active assignments
        if (in_array($request->status, ['retired', 'disposed', 'lost', 'damaged', 'inactive'])) {
            $assignment = $asset->currentAssignment;

            if ($assignment && is_null($assignment->returned_at)) {
                $assignment->update([
                    'returned_at' => $request->returned_at ?? now(),
                    'remarks' => $request->remarks,
                    'updated_by' => auth()->id(),
                ]);
            }
        }

        return redirect()->back()->with('success', 'Asset status updated successfully.');
    }

    public function assignToUser(Request $request)
    {
        $validated = $request->validate([
            'asset_id' => 'required|exists:assets,id',
            'user_id' => 'required|exists:users,id',
            'remarks' => 'nullable|string|max:500',
        ]);

        $asset = Asset::findOrFail($validated['asset_id']);

        // Check if asset is available for assignment
        if ($asset->status !== 'available') {
            return redirect()->back()->with('error', 'Asset is not available for assignment.');
        }

        // Update asset status
        $asset->update([
            'status' => 'assigned',
            'updated_by' => Auth::id(),
        ]);

        // Create assignment record
        $asset->assignments()->create([
            'assigned_to' => $validated['user_id'],
            'assigned_at' => now(),
            'assigned_by' => Auth::id(),
            'remarks' => $validated['remarks'],
        ]);

        return redirect()->back()->with('success', 'Asset assigned successfully.');
    }

    public function unassignAsset(Request $request, Asset $asset)
    {
        $request->validate([
            'remarks' => 'nullable|string|max:500',
            'returned_at' => 'required|date',
        ]);

        $assignment = $asset->currentAssignment;

        if (! $assignment) {
            return redirect()->back()->with('error', 'No active assignment found for this asset.');
        }

        $assignment->update([
            'returned_at' => $request->returned_at,
            'remarks' => $request->remarks,
            'updated_by' => Auth::id(),
        ]);

        $asset->update([
            'status' => 'available',
            'updated_by' => Auth::id(),
        ]);

        return redirect()->back()->with('success', 'Asset unassigned successfully.');
    }

    public function getModelsByCategory($categoryId)
    {
        $models = ModelType::where('category_type_id', $categoryId)
            ->select('id', 'model_name')
            ->get();

        return response()->json($models);
    }

    public function export(Request $request)
    {
        $request->validate([
            'format' => 'required|in:excel,csv,pdf',
        ]);

        // Implementation for export functionality
        // You can use Laravel Excel package for this

        return response()->json(['message' => 'Export feature to be implemented']);
    }
}
