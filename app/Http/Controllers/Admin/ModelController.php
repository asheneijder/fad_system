<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\ModelType;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ModelController extends Controller
{
    public function __construct(protected ModelType $model) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->query('search');
        $status = $request->query('status');
        $category_id = $request->query('category_id');
        $page = $request->query('page', 1);
        $perPage = $request->query('per_page', 10);

        $models = ModelType::with(['category'])
            ->when($search, function ($query) use ($search) {
                return $query->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('brand', 'like', "%{$search}%")
                    ->orWhere('model_number', 'like', "%{$search}%");
            })
            ->when($status, function ($query) use ($status) {
                return $query->where('status', $status);
            })
            ->when($category_id, function ($query) use ($category_id) {
                return $query->where('category_id', $category_id);
            })
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page)
            ->withQueryString();

        // Get categories for filter
        $categories = Category::where('status', true)->get();

        // Statistics
        $statistics = [
            'total' => ModelType::count(),
            'active' => ModelType::where('status', true)->count(),
            'inactive' => ModelType::where('status', false)->count(),
            'with_images' => ModelType::whereNotNull('image')->count(),
            'without_images' => ModelType::whereNull('image')->count(),
        ];

        return Inertia::render('Admin/Model/Index', [
            'models' => $models,
            'categories' => $categories,
            'statistics' => $statistics,
            'filters' => $request->only(['search', 'status', 'category_id']) + ['page' => $page, 'per_page' => $perPage],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:model_types,name',
            'description' => 'nullable|string|max:1000',
            'category_id' => 'required|exists:categories,id',
            'brand' => 'required|string|max:255',
            'model_number' => 'nullable|string|max:100',
            'specifications' => 'nullable|array',
            'warranty_period' => 'nullable|integer|min:0',
            'status' => 'required|boolean',
            'image' => 'nullable|string|max:500',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        ModelType::create($validated);

        return back()->with('success', 'Model created successfully.');
    }

    public function update(Request $request, ModelType $model)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:model_types,name,'.$model->id,
            'description' => 'nullable|string|max:1000',
            'category_id' => 'required|exists:categories,id',
            'brand' => 'required|string|max:255',
            'model_number' => 'nullable|string|max:100',
            'specifications' => 'nullable|array',
            'warranty_period' => 'nullable|integer|min:0',
            'status' => 'required|boolean',
            'image' => 'nullable|string|max:500',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $model->update($validated);

        return back()->with('success', 'Model updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ModelType $model)
    {
        $model->delete();

        return redirect()->route('admin.models.index')
            ->with('success', 'Model deleted successfully.');
    }

    /**
     * Bulk update status
     */
    public function bulkUpdateStatus(Request $request)
    {
        $validated = $request->validate([
            'model_ids' => 'required|array',
            'model_ids.*' => 'exists:model_types,id',
            'status' => 'required|boolean',
        ]);

        ModelType::whereIn('id', $validated['model_ids'])
            ->update(['status' => $validated['status']]);

        return response()->json([
            'success' => true,
            'message' => count($validated['model_ids']).' model(s) updated successfully.',
        ]);
    }

    /**
     * Bulk delete
     */
    public function bulkDelete(Request $request)
    {
        $validated = $request->validate([
            'model_ids' => 'required|array',
            'model_ids.*' => 'exists:model_types,id',
        ]);

        ModelType::whereIn('id', $validated['model_ids'])->delete();

        return response()->json([
            'success' => true,
            'message' => count($validated['model_ids']).' model(s) deleted successfully.',
        ]);
    }

    /**
     * Export models
     */
    public function export(Request $request)
    {
        $modelIds = $request->input('model_ids', []);

        $models = ModelType::with(['categoryType'])
            ->when(! empty($modelIds), function ($query) use ($modelIds) {
                return $query->whereIn('id', $modelIds);
            })
            ->get();

        $fileName = 'models_'.date('Y-m-d_H-i-s').'.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$fileName.'"',
        ];

        $callback = function () use ($models) {
            $file = fopen('php://output', 'w');

            // Add CSV headers
            fputcsv($file, [
                'Name',
                'Brand',
                'Model Number',
                'Category Type',
                'Description',
                'Warranty Period',
                'Status',
                'Sort Order',
                'Created At',
            ]);

            // Add data rows
            foreach ($models as $model) {
                fputcsv($file, [
                    $model->name,
                    $model->brand,
                    $model->model_number,
                    $model->category ? $model->category->name : '',
                    $model->description,
                    $model->warranty_period,
                    $model->status ? 'Active' : 'Inactive',
                    $model->sort_order,
                    $model->created_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
