<?php

namespace App\Http\Controllers\Admin;

use Inertia\Inertia;
use App\Models\Asset;
use App\Models\CategoryType;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAssetRequest;

class AssetController extends Controller
{
    public function __construct(protected Asset $asset) {}
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

            // dd($assets);

        return Inertia::render('Admin/Asset/Index', [
            'assets' => $assets,
            'filters' => $req->only(['search']) + ['page' => $assets->currentPage()],
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
        $this->asset->create(array_merge(
            $req->validated(),
            [
                'updated_by' => auth()->user()->id,
            ]
        ));

        return to_route('admin.index.asset')->with('success', 'Asset created successfully.');
    }

    public function edit(Asset $asset)
    {
        dd($asset);
        return Inertia::render('Admin/Asset/Edit', [
            'asset' => $asset
        ]);
    }
}
