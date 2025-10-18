<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->query('search');
        $type = $request->query('type');
        $page = $request->query('page', 1);
        $perPage = $request->query('per_page', 10);

        $categories = Category::with('parent')
            ->when($search, function ($query) use ($search) {
                return $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($type, function ($query) use ($type) {
                return $query->where('type', $type);
            })
            ->orderBy('sort_order', 'asc')
            ->orderBy('name', 'asc')
            ->paginate($perPage, ['*'], 'page', $page)
            ->withQueryString();

        return Inertia::render('Admin/Category/Index', [
            'categories' => $categories,
            'filters' => $request->only(['search', 'type']) + ['page' => $page, 'per_page' => $perPage],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:categories,name',
            'description' => 'nullable|string|max:500',
            'type' => 'required|string|in:asset,stationary,equipment,furniture,electronic,other',
            'status' => 'boolean',
            'parent_id' => 'nullable|exists:categories,id',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        Category::create($validated);

        return redirect()->route('admin.categories.index')
            ->with('success', 'Category created successfully.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories')->ignore($category->id),
            ],
            'description' => 'nullable|string|max:500',
            'type' => 'required|string|in:asset,stationary,equipment,furniture,electronic,other',
            'status' => 'boolean',
            'parent_id' => 'nullable|exists:categories,id',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        // Prevent category from being its own parent
        if ($validated['parent_id'] == $category->id) {
            return redirect()->back()->withErrors(['parent_id' => 'Category cannot be its own parent.']);
        }

        $category->update($validated);

        return redirect()->route('admin.categories.index')
            ->with('success', 'Category updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Category $category)
    {
        // Check if category has children
        if ($category->children()->exists()) {
            return redirect()->route('admin.categories.index')
                ->with('error', 'Cannot delete category that has sub-categories.');
        }

        // Check if category has items
        if ($category->items()->exists()) {
            return redirect()->route('admin.categories.index')
                ->with('error', 'Cannot delete category that has items assigned.');
        }

        $category->delete();

        return redirect()->route('admin.categories.index')
            ->with('success', 'Category deleted successfully.');
    }

    /**
     * Get categories by type for dropdowns
     */
    public function getByType($type)
    {
        $categories = Category::where('type', $type)
            ->where('status', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($categories);
    }
}
