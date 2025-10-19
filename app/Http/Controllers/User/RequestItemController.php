<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Jobs\SendStationaryRequestNotification;
use App\Models\Cart;
use App\Models\RequestItem;
use App\Models\RequestItemDetail;
use App\Models\StationaryItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class RequestItemController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->query('search');
        $category = $request->query('category');
        $page = $request->query('page', 1);
        $stationaryPage = $request->query('stationary_page', 1);

        // User requests with pagination
        $requests = RequestItem::with(['items.stationaryItem', 'user'])
            ->where('user_id', auth()->id())
            ->when($search, function ($query) use ($search) {
                return $query->where('purpose', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%");
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10, ['*'], 'page', $page)
            ->withQueryString();

        // Stationary items with pagination and filters
        $stationaryItems = StationaryItem::active()
            ->where('current_stock', '>', 0)
            ->when($search, function ($query) use ($search) {
                return $query->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('unit', 'like', "%{$search}%");
            })
            ->when($category, function ($query) use ($category) {
                return $query->where('category', $category);
            })
            ->orderBy('name')
            ->paginate(12, ['*'], 'stationary_page', $stationaryPage)
            ->withQueryString();

        // Get available categories for filter
        $categories = StationaryItem::active()
            ->where('current_stock', '>', 0)
            ->distinct()
            ->pluck('category')
            ->map(function ($category) {
                return [
                    'label' => ucfirst($category),
                    'value' => $category,
                ];
            });

        $cartItems = Cart::with('stationaryItem')
            ->where('user_id', auth()->id())
            ->get()
            ->toArray();

        return Inertia::render('User/RequestItems/Index', [
            'requests' => $requests,
            'stationaryItems' => $stationaryItems,
            'cartItems' => $cartItems,
            'categories' => $categories,
            'filters' => $request->only(['search', 'category']) + [
                'page' => $page,
                'stationary_page' => $stationaryPage,
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'purpose' => 'required|string|max:255',
            'priority' => 'required|in:low,medium,high,urgent',
            'needed_by' => 'nullable|date|after:today',
            'notes' => 'nullable|string|max:1000',
            'cart_item_ids' => 'required|array',
            'cart_item_ids.*' => 'exists:carts,id,user_id,'.auth()->id(),
        ]);

        $requestItem = DB::transaction(function () use ($validated) {
            // Create the request
            $requestItem = RequestItem::create([
                'user_id' => auth()->id(),
                'purpose' => $validated['purpose'],
                'priority' => $validated['priority'],
                'needed_by' => $validated['needed_by'],
                'notes' => $validated['notes'],
                'status' => 'pending',
            ]);

            // Get cart items
            $cartItems = Cart::with('stationaryItem')
                ->where('user_id', auth()->id())
                ->whereIn('id', $validated['cart_item_ids'])
                ->get();

            // Create request details
            foreach ($cartItems as $cartItem) {
                RequestItemDetail::create([
                    'request_item_id' => $requestItem->id,
                    'stationary_item_id' => $cartItem->stationary_item_id,
                    'quantity' => $cartItem->quantity,
                    'notes' => $cartItem->notes,
                    'unit_price' => $cartItem->stationaryItem->cost_price,
                ]);
            }

            // Clear the cart
            Cart::where('user_id', auth()->id())
                ->whereIn('id', $validated['cart_item_ids'])
                ->delete();

            return $requestItem;
        });

        // Load relationships for the email
        $requestItem->load(['items.stationaryItem', 'user']);

        // Dispatch job to send email notification
        SendStationaryRequestNotification::dispatch($requestItem);

        return redirect()->route('user.request-items.index')
            ->with('success', 'Request submitted successfully. Admins have been notified.');
    }

    /**
     * Display the specified resource.
     */
    public function show(RequestItem $requestItem)
    {
        // Ensure user can only view their own requests
        if ($requestItem->user_id !== auth()->id()) {
            abort(403);
        }

        $requestItem->load(['items.stationaryItem', 'user', 'approvedBy']);

        return Inertia::render('User/RequestItems/Show', [
            'requestItem' => $requestItem,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(RequestItem $requestItem)
    {
        // Ensure user can only cancel their own pending requests
        if ($requestItem->user_id !== auth()->id() || $requestItem->status !== 'pending') {
            abort(403);
        }

        $requestItem->update(['status' => 'cancelled']);

        return redirect()->route('user.request-items.index')
            ->with('success', 'Request cancelled successfully.');
    }
}
