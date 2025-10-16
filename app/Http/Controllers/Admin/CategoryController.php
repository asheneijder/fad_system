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
        $page = $request->query('page', 1);

        $categories = Category::with('parent')
            ->when($search, function ($query) use ($search) {
                return $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('meta_title', 'like', "%{$search}%")
                        ->orWhere('meta_keywords', 'like', "%{$search}%");
                });
            })
            ->orderBy('sort_order', 'asc')
            ->orderBy('name', 'asc')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Admin/Category/Index', [
            'categories' => $categories,
            'filters' => $request->only(['search']) + ['page' => $page],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:categories,name',
            'description' => 'nullable|string|max:1000',
            'type' => 'required|string|in:product,blog,service,document,general',
            'status' => 'boolean',
            'parent_id' => 'nullable|exists:categories,id',
            'sort_order' => 'nullable|integer|min:0',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'meta_keywords' => 'nullable|string|max:255',
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
            'description' => 'nullable|string|max:1000',
            'type' => 'required|string|in:product,blog,service,document,general',
            'status' => 'boolean',
            'parent_id' => 'nullable|exists:categories,id',
            'sort_order' => 'nullable|integer|min:0',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'meta_keywords' => 'nullable|string|max:255',
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

        $category->delete();

        return redirect()->route('admin.categories.index')
            ->with('success', 'Category deleted successfully.');
    }

    /**
     * Bulk update category status
     */
    public function bulkUpdateStatus(Request $request)
    {
        $validated = $request->validate([
            'category_ids' => 'required|array',
            'category_ids.*' => 'exists:categories,id',
            'status' => 'required|boolean',
        ]);

        Category::whereIn('id', $validated['category_ids'])
            ->update(['status' => $validated['status']]);

        return response()->json([
            'success' => true,
            'message' => count($validated['category_ids']).' category(s) updated successfully.',
        ]);
    }

    /**
     * Bulk delete categories
     */
    public function bulkDelete(Request $request)
    {
        $validated = $request->validate([
            'category_ids' => 'required|array',
            'category_ids.*' => 'exists:categories,id',
        ]);

        // Check if any category has children
        $categoriesWithChildren = Category::whereIn('id', $validated['category_ids'])
            ->whereHas('children')
            ->count();

        if ($categoriesWithChildren > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete categories that have sub-categories.',
            ], 422);
        }

        Category::whereIn('id', $validated['category_ids'])->delete();

        return response()->json([
            'success' => true,
            'message' => count($validated['category_ids']).' category(s) deleted successfully.',
        ]);
    }

    /**
     * Export categories
     */
    public function export(Request $request)
    {
        $categoryIds = $request->input('category_ids', []);

        $categories = Category::with('parent')
            ->when(! empty($categoryIds), function ($query) use ($categoryIds) {
                return $query->whereIn('id', $categoryIds);
            })
            ->get();

        $fileName = 'categories_'.date('Y-m-d_H-i-s').'.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$fileName.'"',
        ];

        $callback = function () use ($categories) {
            $file = fopen('php://output', 'w');

            // Add CSV headers
            fputcsv($file, [
                'Name',
                'Type',
                'Parent Category',
                'Description',
                'Sort Order',
                'Status',
                'Meta Title',
                'Meta Description',
                'Meta Keywords',
                'Created At',
            ]);

            // Add data rows
            foreach ($categories as $category) {
                fputcsv($file, [
                    $category->name,
                    $category->type,
                    $category->parent ? $category->parent->name : '',
                    $category->description,
                    $category->sort_order,
                    $category->status ? 'Active' : 'Inactive',
                    $category->meta_title,
                    $category->meta_description,
                    $category->meta_keywords,
                    $category->created_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
