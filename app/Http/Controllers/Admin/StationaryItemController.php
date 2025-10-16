<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StationaryItem;
use App\Models\StationaryItemMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class StationaryItemController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->query('search');
        $page = $request->query('page', 1);

        $stationaryItems = StationaryItem::when($search, function ($query) use ($search) {
            return $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('supplier', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%");
            });
        })
            ->orderBy('name', 'asc')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Admin/StationaryItem/Index', [
            'stationaryItems' => $stationaryItems,
            'filters' => $request->only(['search']) + ['page' => $page],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'category' => 'required|string|in:writing,paper,desk,filing,computer,mailing,cleaning,other',
            'unit' => 'required|string|in:pcs,boxes,packs,reams,sets,bottles,rolls,units',
            'sku' => 'nullable|string|max:100|unique:stationary_items,sku',
            'min_stock' => 'nullable|integer|min:0',
            'current_stock' => 'nullable|integer|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'selling_price' => 'nullable|numeric|min:0',
            'supplier' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
            'status' => 'boolean',
        ]);

        StationaryItem::create($validated);

        return redirect()->route('admin.stationary-items.index')
            ->with('success', 'Stationary item created successfully.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, StationaryItem $stationaryItem)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'category' => 'required|string|in:writing,paper,desk,filing,computer,mailing,cleaning,other',
            'unit' => 'required|string|in:pcs,boxes,packs,reams,sets,bottles,rolls,units',
            'sku' => 'nullable|string|max:100|unique:stationary_items,sku,'.$stationaryItem->id,
            'min_stock' => 'nullable|integer|min:0',
            'current_stock' => 'nullable|integer|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'selling_price' => 'nullable|numeric|min:0',
            'supplier' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
            'status' => 'boolean',
        ]);

        $stationaryItem->update($validated);

        return redirect()->route('admin.stationary-items.index')
            ->with('success', 'Stationary item updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(StationaryItem $stationaryItem)
    {
        $stationaryItem->delete();

        return redirect()->route('admin.stationary-items.index')
            ->with('success', 'Stationary item deleted successfully.');
    }

    /**
     * Update stock for a stationary item
     */
    public function updateStock(Request $request, StationaryItem $stationaryItem)
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:0',
            'operation' => 'required|in:set,add,subtract',
            'type' => 'required|in:in,out,adjustment,return',
            'notes' => 'nullable|string|max:500',
            'movement_date' => 'required|date',
        ]);

        DB::transaction(function () use ($stationaryItem, $validated) {
            $oldStock = $stationaryItem->current_stock;

            switch ($validated['operation']) {
                case 'set':
                    $newStock = $validated['quantity'];
                    break;
                case 'add':
                    $newStock = $oldStock + $validated['quantity'];
                    break;
                case 'subtract':
                    $newStock = max(0, $oldStock - $validated['quantity']);
                    break;
                default:
                    $newStock = $oldStock;
            }

            // Update item stock
            $stationaryItem->update(['current_stock' => $newStock]);

            // Record movement
            StationaryItemMovement::create([
                'stationary_item_id' => $stationaryItem->id,
                'type' => $validated['type'],
                'quantity' => $validated['quantity'],
                'previous_stock' => $oldStock,
                'new_stock' => $newStock,
                'notes' => $validated['notes'],
                'movement_date' => $validated['movement_date'],
                'reference' => 'STOCK_ADJUSTMENT',
            ]);
        });

        return redirect()->route('admin.stationary-items.index')
            ->with('success', 'Stock updated successfully.');
    }

    /**
     * Record movement for a stationary item
     */
    public function recordMovement(Request $request, StationaryItem $stationaryItem)
    {
        $validated = $request->validate([
            'type' => 'required|in:in,out,adjustment,return',
            'quantity' => 'required|integer|min:0',
            'reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:500',
            'movement_date' => 'required|date',
        ]);

        DB::transaction(function () use ($stationaryItem, $validated) {
            $oldStock = $stationaryItem->current_stock;
            $newStock = $oldStock;

            switch ($validated['type']) {
                case 'in':
                case 'return':
                    $newStock = $oldStock + $validated['quantity'];
                    break;
                case 'out':
                    $newStock = max(0, $oldStock - $validated['quantity']);
                    break;
                case 'adjustment':
                    $newStock = $validated['quantity'];
                    break;
            }

            // Update item stock
            $stationaryItem->update(['current_stock' => $newStock]);

            // Record movement
            StationaryItemMovement::create([
                'stationary_item_id' => $stationaryItem->id,
                'type' => $validated['type'],
                'quantity' => $validated['quantity'],
                'previous_stock' => $oldStock,
                'new_stock' => $newStock,
                'notes' => $validated['notes'],
                'movement_date' => $validated['movement_date'],
                'reference' => $validated['reference'] ?? 'MANUAL_ENTRY',
            ]);
        });

        return redirect()->route('admin.stationary-items.index')
            ->with('success', 'Movement recorded successfully.');
    }

    /**
     * Bulk update item status
     */
    public function bulkUpdateStatus(Request $request)
    {
        $validated = $request->validate([
            'item_ids' => 'required|array',
            'item_ids.*' => 'exists:stationary_items,id',
            'status' => 'required|boolean',
        ]);

        StationaryItem::whereIn('id', $validated['item_ids'])
            ->update(['status' => $validated['status']]);

        return response()->json([
            'success' => true,
            'message' => count($validated['item_ids']).' item(s) updated successfully.',
        ]);
    }

    /**
     * Export stationary items
     */
    public function export(Request $request)
    {
        $itemIds = $request->input('item_ids', []);

        $items = StationaryItem::when(! empty($itemIds), function ($query) use ($itemIds) {
            return $query->whereIn('id', $itemIds);
        })
            ->get();

        $fileName = 'stationary_items_'.date('Y-m-d_H-i-s').'.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$fileName.'"',
        ];

        $callback = function () use ($items) {
            $file = fopen('php://output', 'w');

            // Add CSV headers
            fputcsv($file, [
                'Name',
                'SKU',
                'Category',
                'Unit',
                'Current Stock',
                'Minimum Stock',
                'Cost Price',
                'Selling Price',
                'Supplier',
                'Location',
                'Status',
                'Description',
                'Created At',
            ]);

            // Add data rows
            foreach ($items as $item) {
                fputcsv($file, [
                    $item->name,
                    $item->sku,
                    $item->category,
                    $item->unit,
                    $item->current_stock,
                    $item->min_stock,
                    $item->cost_price,
                    $item->selling_price,
                    $item->supplier,
                    $item->location,
                    $item->status ? 'Active' : 'Inactive',
                    $item->description,
                    $item->created_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Get item movements
     */
    public function movements(StationaryItem $stationaryItem)
    {
        $movements = $stationaryItem->movements()
            ->orderBy('movement_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return Inertia::render('Admin/StationaryItem/Movements', [
            'stationaryItem' => $stationaryItem,
            'movements' => $movements,
        ]);
    }
}
