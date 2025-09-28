<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CategoryType;
use App\Models\ModelType;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ModelController extends Controller
{
    public function __construct(protected ModelType $model) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $req)
    {
        $search = $req->query('search');

        $models = $this->model->query()
            ->with(['categoryType'])
            ->when(
                $search,
                fn ($query) => $query->where('model_name', 'like', "%{$search}%")
                    ->orWhere('model_no', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
            )
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        // Get category types for the dropdown
        $categoryTypes = CategoryType::select('id', 'category_name')
            ->orderBy('category_name')
            ->get();

        return Inertia::render('Admin/Model/Index', [
            'models' => $models,
            'categoryTypes' => $categoryTypes,
            'filters' => $req->only(['search']) + ['page' => $models->currentPage()],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     * This method can be removed since we're using modals
     */
    public function create()
    {
        // This method can be removed or kept for backward compatibility
        $categoryTypes = CategoryType::select('id', 'category_name')
            ->orderBy('category_name')
            ->get();

        return Inertia::render('Admin/Model/Create', [
            'categoryTypes' => $categoryTypes,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $req)
    {
        $validated = $req->validate([
            'model_name' => 'required|string|max:255',
            'model_no' => 'nullable|string|max:255',
            'description' => 'required|string|max:500',
            'category_type_id' => 'required|exists:category_types,id',
        ]);

        $model = $this->model->create($validated);

        // Return JSON response for AJAX requests (modals)
        if ($req->wantsJson() || $req->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Model created successfully',
                'model' => $model->load('categoryType'),
            ]);
        }

        // Fallback for non-AJAX requests
        return redirect()->route('admin.models.index')->with('success', 'Model created successfully');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $model = $this->model->findOrFail($id);
        $model->load(['categoryType']);

        return Inertia::render('Admin/Model/Show', [
            'model' => $model,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     * This method can be removed since we're using modals
     */
    public function edit(string $id)
    {
        // This method can be removed or kept for backward compatibility
        $model = $this->model->findOrFail($id);
        $model->load(['categoryType']);

        $categoryTypes = CategoryType::select('id', 'category_name')
            ->orderBy('category_name')
            ->get();

        return Inertia::render('Admin/Model/Edit', [
            'model' => $model,
            'categoryTypes' => $categoryTypes,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $req, string $id)
    {
        $model = $this->model->findOrFail($id);

        $validated = $req->validate([
            'model_name' => 'required|string|max:255',
            'model_no' => 'nullable|string|max:255',
            'description' => 'required|string|max:500',
            'category_type_id' => 'required|exists:category_types,id',
        ]);

        $model->update($validated);

        // Return JSON response for AJAX requests (modals)
        if ($req->wantsJson() || $req->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Model updated successfully',
                'model' => $model->load('categoryType'),
            ]);
        }

        // Fallback for non-AJAX requests
        return redirect()->route('admin.models.index')->with('success', 'Model updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $model = $this->model->findOrFail($id);
        $model->delete();

        // Return JSON response for AJAX requests
        if (request()->wantsJson() || request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Model deleted successfully',
            ]);
        }

        // Fallback for non-AJAX requests
        return redirect()->route('admin.models.index')->with('success', 'Model deleted successfully');
    }
}
