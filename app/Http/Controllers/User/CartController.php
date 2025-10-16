<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\StationaryItem;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CartController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $cartItems = Cart::with('stationaryItem')
            ->where('user_id', auth()->id())
            ->get();

        return Inertia::render('User/Cart/Index', [
            'cartItems' => $cartItems,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'stationary_item_id' => 'required|exists:stationary_items,id',
            'quantity' => 'required|integer|min:1',
            'notes' => 'nullable|string|max:500',
        ]);

        // Check stock availability
        $stationaryItem = StationaryItem::findOrFail($validated['stationary_item_id']);

        if ($stationaryItem->current_stock < $validated['quantity']) {
            return back()->withErrors([
                'quantity' => 'Requested quantity exceeds available stock. Only '.$stationaryItem->current_stock.' items available.',
            ]);
        }

        // Check if item already exists in cart
        $existingCartItem = Cart::where('user_id', auth()->id())
            ->where('stationary_item_id', $validated['stationary_item_id'])
            ->first();

        if ($existingCartItem) {
            // Update quantity if item exists
            $newQuantity = $existingCartItem->quantity + $validated['quantity'];

            if ($stationaryItem->current_stock < $newQuantity) {
                return back()->withErrors([
                    'quantity' => 'Total quantity exceeds available stock. Only '.$stationaryItem->current_stock.' items available.',
                ]);
            }

            $existingCartItem->update([
                'quantity' => $newQuantity,
                'notes' => $validated['notes'] ?: $existingCartItem->notes,
            ]);
        } else {
            // Create new cart item
            Cart::create([
                'user_id' => auth()->id(),
                'stationary_item_id' => $validated['stationary_item_id'],
                'quantity' => $validated['quantity'],
                'notes' => $validated['notes'],
            ]);
        }

        return back()->with('success', 'Item added to cart successfully.');
    }

    public function update(Request $request, $id) // FIXED: Use $id instead of type-hinting
    {
        // Find the cart item with authorization check
        $cart = Cart::where('id', $id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $validated = $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        // Check stock availability
        if ($cart->stationaryItem->current_stock < $validated['quantity']) {
            return back()->withErrors([
                'quantity' => 'Requested quantity exceeds available stock. Only '.$cart->stationaryItem->current_stock.' items available.',
            ]);
        }

        $cart->update($validated);

        return back()->with('success', 'Cart updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id) // FIXED: Use $id instead of type-hinting
    {
        // Find and delete with authorization
        $cart = Cart::where('id', $id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $cart->delete();

        return back()->with('success', 'Item removed from cart successfully.');
    }

    /**
     * Clear all items from cart.
     */
    public function clear()
    {
        Cart::where('user_id', auth()->id())->delete();

        return back()->with('success', 'Cart cleared successfully.');
    }
}
