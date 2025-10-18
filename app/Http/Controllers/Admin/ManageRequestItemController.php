<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RequestItem;
use App\Models\RequestItemDetail;
use App\Models\StationaryItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ManageRequestItemController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->query('search');
        $status = $request->query('status');
        $priority = $request->query('priority');
        $page = $request->query('page', 1);
        $perPage = $request->query('per_page', 10);

        $requests = RequestItem::with(['user', 'approvedBy', 'items.stationaryItem'])
            ->when($search, function ($query) use ($search) {
                return $query->where('purpose', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%");
                    });
            })
            ->when($status, function ($query) use ($status) {
                return $query->where('status', $status);
            })
            ->when($priority, function ($query) use ($priority) {
                return $query->where('priority', $priority);
            })
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page)
            ->withQueryString();

        // Statistics for dashboard
        $statistics = [
            'total' => RequestItem::count(),
            'pending' => RequestItem::where('status', 'pending')->count(),
            'approved' => RequestItem::where('status', 'approved')->count(),
            'rejected' => RequestItem::where('status', 'rejected')->count(),
            'completed' => RequestItem::where('status', 'completed')->count(),
        ];

        return Inertia::render('Admin/ManageRequestItems/Index', [
            'requests' => $requests,
            'statistics' => $statistics,
            'filters' => $request->only(['search', 'status', 'priority']) + ['page' => $page, 'per_page' => $perPage],
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(RequestItem $manageRequestItem)
    {
        $manageRequestItem->load([
            'user',
            'approvedBy',
            'items.stationaryItem',
        ]);

        return Inertia::render('Admin/ManageRequestItems/Show', [
            'requestItem' => $manageRequestItem,
        ]);
    }

    /**
     * Approve the request.
     */
    public function approve(Request $request, RequestItem $manageRequestItem)
    {
        $validated = $request->validate([
            'approved_quantities' => 'required|array',
            'approved_quantities.*' => 'required|integer|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        DB::transaction(function () use ($manageRequestItem, $validated) {
            // Update approved quantities for each item
            foreach ($validated['approved_quantities'] as $itemId => $approvedQuantity) {
                $requestItemDetail = RequestItemDetail::find($itemId);

                if ($requestItemDetail && $requestItemDetail->request_item_id === $manageRequestItem->id) {
                    $requestItemDetail->update([
                        'approved_quantity' => $approvedQuantity,
                    ]);
                }
            }

            // Update request status
            $manageRequestItem->update([
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'notes' => $validated['notes'] ?: $manageRequestItem->notes,
            ]);

            // Deduct from stock (you might want to do this when items are actually issued)
            // $this->deductFromStock($manageRequestItem);
        });

        return redirect()->route('admin.manage-request-items.index')
            ->with('success', 'Request approved successfully.');
    }

    /**
     * Reject the request.
     */
    public function reject(Request $request, RequestItem $manageRequestItem)
    {
        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:1000',
        ]);

        $manageRequestItem->update([
            'status' => 'rejected',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'rejection_reason' => $validated['rejection_reason'],
        ]);

        return redirect()->route('admin.manage-request-items.index')
            ->with('success', 'Request rejected successfully.');
    }

    /**
     * Mark request as completed.
     */
    public function complete(RequestItem $manageRequestItem)
    {
        if ($manageRequestItem->status !== 'approved') {
            return back()->with('error', 'Only approved requests can be marked as completed.');
        }

        DB::transaction(function () use ($manageRequestItem) {
            // Deduct items from stock
            foreach ($manageRequestItem->items as $item) {
                $approvedQuantity = $item->approved_quantity ?? $item->quantity;

                if ($approvedQuantity > 0) {
                    StationaryItem::where('id', $item->stationary_item_id)
                        ->decrement('current_stock', $approvedQuantity);
                }
            }

            // Mark as completed
            $manageRequestItem->update([
                'status' => 'completed',
            ]);
        });

        return redirect()->route('admin.manage-request-items.index')
            ->with('success', 'Request marked as completed and stock updated.');
    }

    /**
     * Update approved quantities.
     */
    public function updateQuantities(Request $request, RequestItem $manageRequestItem)
    {
        $validated = $request->validate([
            'approved_quantities' => 'required|array',
            'approved_quantities.*' => 'required|integer|min:0',
        ]);

        DB::transaction(function () use ($manageRequestItem, $validated) {
            foreach ($validated['approved_quantities'] as $itemId => $approvedQuantity) {
                $requestItemDetail = RequestItemDetail::find($itemId);

                if ($requestItemDetail && $requestItemDetail->request_item_id === $manageRequestItem->id) {
                    // Check if approved quantity doesn't exceed available stock
                    $availableStock = $requestItemDetail->stationaryItem->current_stock;
                    $finalQuantity = min($approvedQuantity, $availableStock);

                    $requestItemDetail->update([
                        'approved_quantity' => $finalQuantity,
                    ]);
                }
            }
        });

        return back()->with('success', 'Quantities updated successfully.');
    }

    /**
     * Deduct items from stock (optional - can be called separately)
     */
    private function deductFromStock(RequestItem $requestItem)
    {
        foreach ($requestItem->items as $item) {
            $approvedQuantity = $item->approved_quantity ?? $item->quantity;

            if ($approvedQuantity > 0) {
                StationaryItem::where('id', $item->stationary_item_id)
                    ->decrement('current_stock', $approvedQuantity);
            }
        }
    }
}
