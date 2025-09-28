<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CategoryType;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class CategoryController extends Controller
{
    public function __construct(protected CategoryType $categoryType) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $req)
    {
        $search = $req->query('search');

        $categories = $this->categoryType->query()
            ->when($search, fn ($q) => $q->where('category_name', 'like', "%{$search}%")
            )
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Admin/Category/Index', [
            'categories' => $categories,
            'filters' => $req->only(['search']) + ['page' => $categories->currentPage()],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     * This method can be removed since we're using modals
     */
    public function create()
    {
        // This method can be removed or kept for backward compatibility
        return Inertia::render('Admin/Category/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_name' => [
                'required',
                'string',
                'max:255',
                'unique:category_types,category_name',
            ],
        ], [
            'category_name.required' => 'Category name is required.',
            'category_name.unique' => 'This category name already exists.',
            'category_name.max' => 'Category name must not exceed 255 characters.',
        ]);

        $category = $this->categoryType->create($validated);

        // Return JSON response for AJAX requests (modals)
        if ($request->wantsJson() || $request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Category created successfully',
                'category' => $category,
            ]);
        }

        // Fallback for non-AJAX requests
        return redirect()->route('admin.categories.index')->with('success', 'Category created successfully');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $category = $this->categoryType->findOrFail($id);

        return Inertia::render('Admin/Category/Show', [
            'category' => $category,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     * This method can be removed since we're using modals
     */
    public function edit(string $id)
    {
        // This method can be removed or kept for backward compatibility
        $category = $this->categoryType->findOrFail($id);

        return Inertia::render('Admin/Category/Edit', [
            'category' => $category,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $category = $this->categoryType->findOrFail($id);

        $validated = $request->validate([
            'category_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('category_types', 'category_name')->ignore($category->id),
            ],
        ], [
            'category_name.required' => 'Category name is required.',
            'category_name.unique' => 'This category name already exists.',
            'category_name.max' => 'Category name must not exceed 255 characters.',
        ]);

        $category->update($validated);

        // Return JSON response for AJAX requests (modals)
        if ($request->wantsJson() || $request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Category updated successfully',
                'category' => $category,
            ]);
        }

        // Fallback for non-AJAX requests
        return redirect()->route('admin.categories.index')->with('success', 'Category updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $category = $this->categoryType->findOrFail($id);

        // Check if category is being used by any models
        $modelsCount = $category->modelTypes()->count();

        if ($modelsCount > 0) {
            // Return error if category is in use
            if (request()->wantsJson() || request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => "Cannot delete category. It is being used by {$modelsCount} model(s).",
                ], 422);
            }

            return redirect()->route('admin.categories.index')
                ->with('error', "Cannot delete category. It is being used by {$modelsCount} model(s).");
        }

        $category->delete();

        // Return JSON response for AJAX requests
        if (request()->wantsJson() || request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Category deleted successfully',
            ]);
        }

        // Fallback for non-AJAX requests
        return redirect()->route('admin.categories.index')->with('success', 'Category deleted successfully');
    }

    /**
     * Get categories for dropdown/select options
     */
    public function getOptions()
    {
        $categories = $this->categoryType->select('id', 'category_name')
            ->orderBy('category_name')
            ->get();

        return response()->json($categories);
    }
}
