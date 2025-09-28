<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StationaryItem;
use App\Models\StationaryItemMovement;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class StationaryItemController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $req)
    {
        $search = $req->query('search');

        $stationaryItems = StationaryItem::with(['stationaryItemMovements' => function ($query) {
            $query->latest('movement_date')->limit(1); // Only get the latest movement
        }])
            ->when($search, fn ($q) => $q->where('description', 'like', "%{$search}%")
                ->orWhere('unit', 'like', "%{$search}%")
            )
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Admin/StationaryItem/Index', [
            'stationaryItems' => $stationaryItems,
            'filters' => $req->only(['search']) + ['page' => $stationaryItems->currentPage()],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Admin/StationaryItem/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'description' => 'required|string|max:500',
            'unit' => 'required|string|max:50',
            'unit_cost' => 'required|numeric|min:0',
            'status' => 'required|boolean',
            'initial_stock' => 'nullable|numeric|min:0',
        ], [
            'description.required' => 'Description is required.',
            'unit.required' => 'Unit is required.',
            'unit_cost.required' => 'Unit cost is required.',
            'unit_cost.min' => 'Unit cost cannot be negative.',
            'status.required' => 'Status is required.',
            'initial_stock.min' => 'Initial stock cannot be negative.',
        ]);

        DB::beginTransaction();
        try {
            // Create the stationary item
            $item = StationaryItem::create([
                'description' => $validated['description'],
                'unit' => $validated['unit'],
                'unit_cost' => $validated['unit_cost'],
                'status' => $validated['status'],
            ]);

            // Create initial stock movement if initial stock is provided
            if (isset($validated['initial_stock']) && $validated['initial_stock'] > 0) {
                StationaryItemMovement::create([
                    'stationary_item_id' => $item->id,
                    'movement_date' => Carbon::now(),
                    'movement_type' => 'opening_balance',
                    'in' => $validated['initial_stock'],
                    'out' => 0,
                    'opening_balance' => 0,
                    'closing_balance' => $validated['initial_stock'],
                    'remarks' => 'Initial stock entry',
                ]);
            }

            DB::commit();

            // Load the item with its latest movement for response
            $item->load(['stationaryItemMovements' => function ($query) {
                $query->latest('movement_date')->limit(1);
            }]);

            // Return JSON response for AJAX requests (modals)
            if ($request->wantsJson() || $request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Stationary item created successfully',
                    'item' => $item,
                ]);
            }

            return redirect()->route('admin.stationary-items.index')
                ->with('success', 'Stationary item created successfully');

        } catch (\Exception $e) {
            DB::rollBack();

            if ($request->wantsJson() || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create stationary item',
                    'error' => $e->getMessage(),
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Failed to create stationary item')
                ->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $item = StationaryItem::with(['stationaryItemMovements' => function ($query) {
            $query->orderBy('movement_date', 'desc');
        }])->findOrFail($id);

        return Inertia::render('Admin/StationaryItem/Show', [
            'item' => $item,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $item = StationaryItem::findOrFail($id);

        return Inertia::render('Admin/StationaryItem/Edit', [
            'item' => $item,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $item = StationaryItem::findOrFail($id);

        $validated = $request->validate([
            'description' => 'required|string|max:500',
            'unit' => 'required|string|max:50',
            'unit_cost' => 'required|numeric|min:0',
            'status' => 'required|boolean',
        ], [
            'description.required' => 'Description is required.',
            'unit.required' => 'Unit is required.',
            'unit_cost.required' => 'Unit cost is required.',
            'unit_cost.min' => 'Unit cost cannot be negative.',
            'status.required' => 'Status is required.',
        ]);

        $item->update($validated);

        // Load the item with its latest movement for response
        $item->load(['stationaryItemMovements' => function ($query) {
            $query->latest('movement_date')->limit(1);
        }]);

        // Return JSON response for AJAX requests (modals)
        if ($request->wantsJson() || $request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Stationary item updated successfully',
                'item' => $item,
            ]);
        }

        return redirect()->route('admin.stationary-items.index')
            ->with('success', 'Stationary item updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $item = StationaryItem::findOrFail($id);

        DB::beginTransaction();
        try {
            // Delete all related movements first
            $item->stationaryItemMovements()->delete();

            // Delete the item
            $item->delete();

            DB::commit();

            // Return JSON response for AJAX requests
            if (request()->wantsJson() || request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Stationary item deleted successfully',
                ]);
            }

            return redirect()->route('admin.stationary-items.index')
                ->with('success', 'Stationary item deleted successfully');

        } catch (\Exception $e) {
            DB::rollBack();

            if (request()->wantsJson() || request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete stationary item',
                    'error' => $e->getMessage(),
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Failed to delete stationary item');
        }
    }

    /**
     * Create stock movement (IN)
     */
    public function stockIn(Request $request, string $id)
    {
        $item = StationaryItem::findOrFail($id);

        $validated = $request->validate([
            'quantity' => 'required|numeric|min:0.01',
            'movement_date' => 'required|date',
            'remarks' => 'nullable|string|max:255',
        ]);

        $this->createStockMovement($item, 'in', $validated);

        return response()->json([
            'success' => true,
            'message' => 'Stock added successfully',
        ]);
    }

    /**
     * Create stock movement (OUT)
     */
    public function stockOut(Request $request, string $id)
    {
        $item = StationaryItem::findOrFail($id);

        $validated = $request->validate([
            'quantity' => 'required|numeric|min:0.01',
            'movement_date' => 'required|date',
            'remarks' => 'nullable|string|max:255',
        ]);

        // Check if there's enough stock
        $currentStock = $this->getCurrentStock($item);
        if ($currentStock < $validated['quantity']) {
            return response()->json([
                'success' => false,
                'message' => "Insufficient stock. Current stock: {$currentStock}",
            ], 422);
        }

        $this->createStockMovement($item, 'out', $validated);

        return response()->json([
            'success' => true,
            'message' => 'Stock deducted successfully',
        ]);
    }

    /**
     * Get current stock for an item
     */
    private function getCurrentStock(StationaryItem $item)
    {
        $latestMovement = $item->stationaryItemMovements()
            ->latest('movement_date')
            ->latest('id')
            ->first();

        return $latestMovement ? $latestMovement->closing_balance : 0;
    }

    /**
     * Create stock movement record
     */
    private function createStockMovement(StationaryItem $item, string $type, array $data)
    {
        DB::beginTransaction();
        try {
            $currentStock = $this->getCurrentStock($item);

            $movement = StationaryItemMovement::create([
                'stationary_item_id' => $item->id,
                'movement_date' => $data['movement_date'],
                'movement_type' => $type,
                'in' => $type === 'in' ? $data['quantity'] : 0,
                'out' => $type === 'out' ? $data['quantity'] : 0,
                'opening_balance' => $currentStock,
                'closing_balance' => $type === 'in'
                    ? $currentStock + $data['quantity']
                    : $currentStock - $data['quantity'],
                'remarks' => $data['remarks'] ?? null,
            ]);

            DB::commit();

            return $movement;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Monthly stock carry forward
     * This should be run at the end of each month
     */
    public function monthlyCarryForward()
    {
        $currentDate = Carbon::now();
        $lastMonth = $currentDate->copy()->subMonth();

        // Get all active items
        $items = StationaryItem::where('status', true)->get();

        DB::beginTransaction();
        try {
            foreach ($items as $item) {
                $currentStock = $this->getCurrentStock($item);

                // Only create carry forward if there's stock
                if ($currentStock > 0) {
                    StationaryItemMovement::create([
                        'stationary_item_id' => $item->id,
                        'movement_date' => $currentDate->startOfMonth(),
                        'movement_type' => 'carry_forward',
                        'in' => 0,
                        'out' => 0,
                        'opening_balance' => $currentStock,
                        'closing_balance' => $currentStock,
                        'remarks' => "Carry forward from {$lastMonth->format('M Y')}",
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Monthly carry forward completed successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to complete monthly carry forward',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get stock summary report
     */
    public function stockSummary()
    {
        $items = StationaryItem::with(['stationaryItemMovements' => function ($query) {
            $query->latest('movement_date')->limit(1);
        }])
            ->where('status', true)
            ->get()
            ->map(function ($item) {
                $latestMovement = $item->stationaryItemMovements->first();
                $currentStock = $latestMovement ? $latestMovement->closing_balance : 0;
                $stockValue = $currentStock * $item->unit_cost;

                return [
                    'id' => $item->id,
                    'description' => $item->description,
                    'unit' => $item->unit,
                    'unit_cost' => $item->unit_cost,
                    'current_stock' => $currentStock,
                    'stock_value' => $stockValue,
                    'status' => $this->getStockStatus($currentStock),
                ];
            });

        $totalValue = $items->sum('stock_value');
        $lowStockCount = $items->where('status', 'low')->count();
        $outOfStockCount = $items->where('status', 'out_of_stock')->count();

        return Inertia::render('Admin/StationaryItem/StockSummary', [
            'items' => $items,
            'summary' => [
                'total_items' => $items->count(),
                'total_value' => $totalValue,
                'low_stock_count' => $lowStockCount,
                'out_of_stock_count' => $outOfStockCount,
            ],
        ]);
    }

    /**
     * Get stock status based on quantity
     */
    private function getStockStatus($stock)
    {
        if ($stock <= 0) {
            return 'out_of_stock';
        }
        if ($stock <= 10) {
            return 'low';
        } // You can make this configurable

        return 'normal';
    }

    /**
     * Get stock movement history for an item
     */
    public function movementHistory(string $id)
    {
        $item = StationaryItem::findOrFail($id);

        $movements = StationaryItemMovement::where('stationary_item_id', $id)
            ->orderBy('movement_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(20);

        return Inertia::render('Admin/StationaryItem/MovementHistory', [
            'item' => $item,
            'movements' => $movements,
        ]);
    }
}
