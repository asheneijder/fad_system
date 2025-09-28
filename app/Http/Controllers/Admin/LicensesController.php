<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\License;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LicensesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $req)
    {
        $search = $req->query('search');

        $licenses = License::when(
            $search,
            fn ($q) => $q->where('license_name', 'like', "%{$search}%")
                ->orWhere('product_key', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%")
        )
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Admin/Licenses/Index', [
            'licenses' => $licenses,
            'filters' => $req->only(['search']) + ['page' => $licenses->currentPage()],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     * This method can be removed since we're using modals
     */
    public function create()
    {
        return Inertia::render('Admin/Licenses/Create');
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
            'min_qty' => 'nullable|integer|min:0',
            'available_qty' => 'nullable|integer|min:0',
            'status' => 'required|boolean',
            // 'description' => 'nullable|string|max:500',
        ], [
            'license_name.required' => 'License name is required.',
            'product_key.required' => 'Product key is required.',
            'product_key.unique' => 'This product key already exists.',
            'expiration_date.required' => 'Expiration date is required.',
            'expiration_date.after' => 'Expiration date must be in the future.',
            'min_qty.min' => 'Minimum quantity cannot be negative.',
            'available_qty.min' => 'Available quantity cannot be negative.',
            'status.required' => 'Status is required.',
        ]);

        // Set defaults
        $validated['min_qty'] = $validated['min_qty'] ?? 1;
        $validated['available_qty'] = $validated['available_qty'] ?? 0;

        $license = License::create($validated);

        // Return JSON response for AJAX requests (modals)
        if ($request->wantsJson() || $request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'License created successfully',
                'license' => $license,
            ]);
        }

        // Fallback for non-AJAX requests
        return redirect()->route('admin.licenses.index')->with('success', 'License created successfully');
    }

    /**
     * Display the specified resource.
     */
    public function show(License $license)
    {
        // You can add relationships here if needed
        // $license->load(['assignments', 'usageLogs']);

        return Inertia::render('Admin/Licenses/Show', [
            'license' => $license,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     * This method can be removed since we're using modals
     */
    public function edit(License $license)
    {
        return Inertia::render('Admin/Licenses/Edit', [
            'license' => $license,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, License $license)
    {
        $validated = $request->validate([
            'license_name' => 'required|string|max:255',
            'product_key' => [
                'required',
                'string',
                'max:255',
                'unique:licenses,product_key,'.$license->id,
            ],
            'expiration_date' => 'required|date',
            'min_qty' => 'nullable|integer|min:0',
            'available_qty' => 'nullable|integer|min:0',
            'status' => 'required|boolean',
            'description' => 'nullable|string|max:500',
        ], [
            'license_name.required' => 'License name is required.',
            'product_key.required' => 'Product key is required.',
            'product_key.unique' => 'This product key already exists.',
            'expiration_date.required' => 'Expiration date is required.',
            'min_qty.min' => 'Minimum quantity cannot be negative.',
            'available_qty.min' => 'Available quantity cannot be negative.',
            'status.required' => 'Status is required.',
        ]);

        // Set defaults if null
        $validated['min_qty'] = $validated['min_qty'] ?? 1;
        $validated['available_qty'] = $validated['available_qty'] ?? 0;

        $license->update($validated);

        // Return JSON response for AJAX requests (modals)
        if ($request->wantsJson() || $request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'License updated successfully',
                'license' => $license->fresh(),
            ]);
        }

        // Fallback for non-AJAX requests
        return redirect()->route('admin.licenses.index')->with('success', 'License updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(License $license)
    {
        // Check if license is being used/assigned
        // Uncomment and modify based on your relationships
        /*
        $assignmentsCount = $license->assignments()->count();

        if ($assignmentsCount > 0) {
            if (request()->wantsJson() || request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => "Cannot delete license. It has {$assignmentsCount} assignment(s).",
                ], 422);
            }

            return redirect()->route('admin.licenses.index')
                ->with('error', "Cannot delete license. It has {$assignmentsCount} assignment(s).");
        }
        */

        $license->delete();

        // Return JSON response for AJAX requests
        if (request()->wantsJson() || request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'License deleted successfully',
            ]);
        }

        // Fallback for non-AJAX requests
        return redirect()->route('admin.licenses.index')->with('success', 'License deleted successfully');
    }

    /**
     * Generate a random product key
     */
    public function generateProductKey()
    {
        do {
            $segments = [];
            for ($i = 0; $i < 4; $i++) {
                $segment = strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 4));
                $segments[] = $segment;
            }
            $productKey = implode('-', $segments);
        } while (License::where('product_key', $productKey)->exists());

        return response()->json([
            'success' => true,
            'product_key' => $productKey,
        ]);
    }

    /**
     * Update license quantities (for stock management)
     */
    public function updateQuantity(Request $request, License $license)
    {
        $validated = $request->validate([
            'available_qty' => 'required|integer|min:0',
            'operation' => 'required|in:set,add,subtract',
            'notes' => 'nullable|string|max:255',
        ]);

        $currentQty = $license->available_qty ?? 0;

        switch ($validated['operation']) {
            case 'set':
                $newQty = $validated['available_qty'];
                break;
            case 'add':
                $newQty = $currentQty + $validated['available_qty'];
                break;
            case 'subtract':
                $newQty = max(0, $currentQty - $validated['available_qty']);
                break;
        }

        $license->update(['available_qty' => $newQty]);

        // Optional: Log the quantity change
        // QuantityLog::create([
        //     'license_id' => $license->id,
        //     'old_quantity' => $currentQty,
        //     'new_quantity' => $newQty,
        //     'operation' => $validated['operation'],
        //     'notes' => $validated['notes'],
        //     'user_id' => auth()->id(),
        // ]);

        return response()->json([
            'success' => true,
            'message' => 'License quantity updated successfully',
            'license' => $license->fresh(),
        ]);
    }

    /**
     * Get licenses summary/dashboard data
     */
    public function summary()
    {
        $today = Carbon::today();
        $thirtyDaysFromNow = Carbon::today()->addDays(30);

        $summary = [
            'total_licenses' => License::count(),
            'active_licenses' => License::where('status', true)->count(),
            'expired_licenses' => License::where('expiration_date', '<', $today)->count(),
            'expiring_soon' => License::whereBetween('expiration_date', [$today, $thirtyDaysFromNow])->count(),
            'low_stock' => License::whereRaw('available_qty <= min_qty AND available_qty > 0')->count(),
            'out_of_stock' => License::where('available_qty', 0)->count(),
        ];

        $recentlyExpired = License::where('expiration_date', '<', $today)
            ->where('expiration_date', '>', $today->copy()->subDays(7))
            ->orderBy('expiration_date', 'desc')
            ->limit(5)
            ->get();

        $expiringSoon = License::whereBetween('expiration_date', [$today, $thirtyDaysFromNow])
            ->orderBy('expiration_date')
            ->limit(10)
            ->get();

        $lowStock = License::whereRaw('available_qty <= min_qty AND available_qty > 0')
            ->orderBy('available_qty')
            ->limit(10)
            ->get();

        return Inertia::render('Admin/Licenses/Summary', [
            'summary' => $summary,
            'recently_expired' => $recentlyExpired,
            'expiring_soon' => $expiringSoon,
            'low_stock' => $lowStock,
        ]);
    }

    /**
     * Bulk update license status
     */
    public function bulkUpdateStatus(Request $request)
    {
        $validated = $request->validate([
            'license_ids' => 'required|array|min:1',
            'license_ids.*' => 'exists:licenses,id',
            'status' => 'required|boolean',
        ]);

        $updatedCount = License::whereIn('id', $validated['license_ids'])
            ->update(['status' => $validated['status']]);

        $statusText = $validated['status'] ? 'activated' : 'deactivated';

        return response()->json([
            'success' => true,
            'message' => "{$updatedCount} license(s) {$statusText} successfully",
        ]);
    }

    /**
     * Export licenses data
     */
    public function export()
    {
        $licenses = License::select([
            'license_name',
            'product_key',
            'expiration_date',
            'min_qty',
            'available_qty',
            'status',
            'description',
            'created_at',
        ])
            ->get()
            ->map(function ($license) {
                return [
                    'License Name' => $license->license_name,
                    'Product Key' => $license->product_key,
                    'Expiration Date' => $license->expiration_date->format('Y-m-d'),
                    'Min Quantity' => $license->min_qty,
                    'Available Quantity' => $license->available_qty,
                    'Status' => $license->status ? 'Active' : 'Inactive',
                    'Description' => $license->description ?? 'N/A',
                    'Created Date' => $license->created_at->format('Y-m-d'),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $licenses,
            'filename' => 'licenses_'.now()->format('Y-m-d').'.csv',
        ]);
    }

    /**
     * Check license validity by product key
     */
    public function checkLicense(Request $request)
    {
        $validated = $request->validate([
            'product_key' => 'required|string',
        ]);

        $license = License::where('product_key', $validated['product_key'])->first();

        if (! $license) {
            return response()->json([
                'valid' => false,
                'message' => 'License not found',
            ], 404);
        }

        $isExpired = Carbon::parse($license->expiration_date)->isPast();
        $isActive = $license->status;

        return response()->json([
            'valid' => $isActive && ! $isExpired,
            'license' => $license,
            'is_expired' => $isExpired,
            'is_active' => $isActive,
            'expires_at' => $license->expiration_date,
            'days_until_expiry' => Carbon::parse($license->expiration_date)->diffInDays(Carbon::now(), false),
        ]);
    }
}
