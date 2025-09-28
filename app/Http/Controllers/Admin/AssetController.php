<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use Inertia\Inertia;
use App\Models\Asset;
use App\Models\CategoryType;
use Illuminate\Http\Request;
use App\Models\AssetAssignment;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\StoreAssetRequest;
use App\Http\Requests\UpdateAssetRequest;

class AssetController extends Controller
{
    public function __construct(protected Asset $asset, protected User $user, protected AssetAssignment $assetAssignment) {}
    
    public function index(Request $req)
    {
        $search = $req->query('search');

        $assets = $this->asset->query()
            ->with([
                'modelType.categoryType',
                'users'
            ])
            ->when(
                $search,
                fn($query) =>
                $query->where('asset_name', 'like', "%{$search}%")
                    ->orWhere('asset_tag_no', 'like', "%{$search}%")
                    ->orWhere('serial_no', 'like', "%{$search}%")
            )
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Admin/Asset/Index', [
            'assets' => $assets,
            'filters' => $req->only(['search']) + ['page' => $assets->currentPage()],
            'users' => $this->user->all()
        ]);
    }

    public function create(CategoryType $categoryType)
    {
        $getCategoryTypes = $categoryType->query()
            ->with(['modelTypes'])
            ->get();

        return Inertia::render('Admin/Asset/Create', [
            'categories' => $getCategoryTypes
        ]);
    }

    public function store(StoreAssetRequest $req)
    {
        dd($req->all());
        $data = $req->validated();

        unset($data['image']);

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
        return Inertia::render('Admin/Asset/Edit', [
            'asset' => $asset->load('modelType.categoryType', 'media'),
            'categories' => CategoryType::with('modelTypes')->get(),
        ]);
    }

    public function update(UpdateAssetRequest $request, Asset $asset)
    {
        $validated = $request->validated();

        dd($validated);

        $asset->update($validated);

        if ($request->hasFile('image')) {
            $asset->clearMediaCollection('images');
            $asset->addMediaFromRequest('image')->toMediaCollection('images');
        }

        return redirect()->route('admin.assets.index')->with('success', 'Asset updated successfully.');
    }

    public function destroy(Asset $asset)
    {
        $asset->delete();

        return to_route('admin.assets.index')->with('success', 'Asset deleted successfully.');
    }

    public function show(Asset $asset)
    {
        return Inertia::render('Admin/Asset/Show', [
            'asset' => $asset->load([
                'modelType.categoryType',
                'users',
                'media',
                'currentAssignment.user',
                'assignments.user',
            ]),
        ]);
    }

    public function updateStatus(Request $request, Asset $asset)
    {
        $request->validate([
            'status' => 'required|string',
            'remarks' => 'nullable|string',
            'returned_at' => 'nullable|date',
        ]);

        $asset->update([
            'status' => $request->status,
        ]);

        $asset->load('currentAssignment');

        if (in_array($request->status, ['retired', 'disposed', 'lost', 'damaged'])) {
            $assignment = $asset->assignments->firstWhere('returned_at', null);

            if ($assignment && is_null($assignment->returned_at)) {
                $assignment->update([
                    'returned_at' => $request->returned_at ?? now(),
                    'remarks' => $request->remarks,
                ]);
            }
        }

        return redirect()->back()->with('success', 'Asset status updated.');
    }

    public function assignToUser(Request $request)
    {
        $validated = $request->validate([
            'asset_id' => 'required|exists:assets,id',
            'user_id' => 'required|exists:users,id',
        ]);

        $asset = Asset::findOrFail($validated['asset_id']);

        $asset->update([
            'status' => 'assigned',
        ]);

        $asset->assignments()->create([
            'asset_id'    => $asset->id,
            'assigned_to' => $validated['user_id'],
            'assigned_at' => now(),
            'assigned_by' => Auth::id(),
            'remarks'     => null,
        ]);

        return redirect()->back()->with('success', 'Asset assigned successfully.');
    }
}
