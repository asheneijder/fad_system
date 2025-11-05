<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\License;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class LicensesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->query('search');
        $page = $request->query('page', 1);
        $perPage = $request->query('per_page', 10);

        $licenses = License::when($search, function ($query) use ($search) {
            return $query->where(function ($q) use ($search) {
                $q->where('license_name', 'like', "%{$search}%")
                    ->orWhere('product_key', 'like', "%{$search}%")
                    ->orWhere('manufacturer', 'like', "%{$search}%")
                    ->orWhere('licensed_email', 'like', "%{$search}%")
                    ->orWhere('licensed_name', 'like', "%{$search}%");
            });
        })
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page)
            ->withQueryString();

        return Inertia::render('Admin/Licenses/Index', [
            'licenses' => $licenses,
            'filters' => $request->only(['search', 'per_page']) + ['page' => $page],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'license_name' => 'required|string|max:255',
            'product_key' => 'required|string|max:255|unique:licenses,product_key',
            'expiration_date' => 'required|date|after:today',
            'licensed_email' => 'nullable|email|max:255',
            'licensed_name' => 'nullable|string|max:255',
            'manufacturer' => 'nullable|string|max:255',
            'min_qty' => 'nullable|integer|min:0',
            'total_qty' => 'required|integer|min:1',
            'available_qty' => 'nullable|integer|min:0|max:'.$request->total_qty,
            'status' => 'boolean',
        ]);

        License::create($validated);

        return redirect()->route('admin.licenses.index')
            ->with('success', 'License created successfully.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, License $license)
    {
        $validated = $request->validate([
            'license_name' => 'required|string|max:255',
            'product_key' => 'required|string|max:255|unique:licenses,product_key,'.$license->id,
            'expiration_date' => 'required|date',
            'licensed_email' => 'nullable|email|max:255',
            'licensed_name' => 'nullable|string|max:255',
            'manufacturer' => 'nullable|string|max:255',
            'min_qty' => 'nullable|integer|min:0',
            'total_qty' => 'required|integer|min:1',
            'available_qty' => 'nullable|integer|min:0|max:'.$request->total_qty,
            'status' => 'boolean',
        ]);

        $license->update($validated);

        return redirect()->route('admin.licenses.index')
            ->with('success', 'License updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(License $license)
    {
        $license->delete();

        return redirect()->route('admin.licenses.index')
            ->with('success', 'License deleted successfully.');
    }

    /**
     * Generate a random product key
     */
    public function generateProductKey()
    {
        try {
            // Generate a simple unique key without database check first
            $productKey = 'PK-'.time().'-'.Str::random(6);

            return response()->json([
                'success' => true,
                'product_key' => strtoupper($productKey),
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to generate product key: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate product key',
            ], 500);
        }
    }

    /**
     * Update license quantity
     */
    public function updateQuantity(Request $request, License $license)
    {
        $validated = $request->validate([
            'available_qty' => 'required|integer|min:0',
            'operation' => 'required|in:set,add,subtract',
            'notes' => 'nullable|string|max:500',
        ]);

        $newQuantity = $license->available_qty;

        switch ($validated['operation']) {
            case 'set':
                $newQuantity = $validated['available_qty'];
                break;
            case 'add':
                $newQuantity += $validated['available_qty'];
                break;
            case 'subtract':
                $newQuantity = max(0, $newQuantity - $validated['available_qty']);
                break;
        }

        // Ensure available quantity doesn't exceed total quantity
        $newQuantity = min($newQuantity, $license->total_qty);

        $license->update([
            'available_qty' => $newQuantity,
        ]);

        return redirect()->route('admin.licenses.index')
            ->with('success', 'License quantity updated successfully.');
    }

    /**
     * Bulk update license status
     */
    public function bulkUpdateStatus(Request $request)
    {
        $validated = $request->validate([
            'license_ids' => 'required|array',
            'license_ids.*' => 'exists:licenses,id',
            'status' => 'required|boolean',
        ]);

        License::whereIn('id', $validated['license_ids'])
            ->update(['status' => $validated['status']]);

        return response()->json([
            'success' => true,
            'message' => count($validated['license_ids']).' license(s) updated successfully.',
        ]);
    }

    /**
     * Export licenses
     */
    public function export(Request $request)
    {
        $licenseIds = $request->input('license_ids', []);

        $licenses = License::when(! empty($licenseIds), function ($query) use ($licenseIds) {
            return $query->whereIn('id', $licenseIds);
        })->get();

        // For CSV export
        $fileName = 'licenses_'.date('Y-m-d_H-i-s').'.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$fileName.'"',
        ];

        $callback = function () use ($licenses) {
            $file = fopen('php://output', 'w');

            // Add CSV headers
            fputcsv($file, [
                'License Name',
                'Product Key',
                'Manufacturer',
                'Expiration Date',
                'Licensed Email',
                'Licensed To',
                'Total Quantity',
                'Available Quantity',
                'Minimum Quantity',
                'Status',
                'Created At',
            ]);

            // Add data rows
            foreach ($licenses as $license) {
                fputcsv($file, [
                    $license->license_name,
                    $license->product_key,
                    $license->manufacturer,
                    $license->expiration_date,
                    $license->licensed_email,
                    $license->licensed_name,
                    $license->total_qty,
                    $license->available_qty,
                    $license->min_qty,
                    $license->status ? 'Active' : 'Inactive',
                    $license->created_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
