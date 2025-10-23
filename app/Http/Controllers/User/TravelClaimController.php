<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\TravelClaim;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class TravelClaimController extends Controller
{
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('User/TravelClaim/Create', [
            'defaultRates' => [
                'car' => 0.75,
                'motorcycle' => 0.45,
            ],
            'vehicleTypes' => [
                ['value' => 'car', 'label' => 'Car'],
                ['value' => 'motorcycle', 'label' => 'Motorcycle'],
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'vehicle_type' => 'required|in:car,motorcycle,bicycle,other',
            'registration_plate_number' => 'nullable|string|max:20',
            'cubic_capacity' => 'nullable|integer|min:0',
            'is_multiple_legs' => 'boolean',
            'travel_legs' => 'required|array|min:1',
            'travel_legs.*.from' => 'required|string|max:255',
            'travel_legs.*.to' => 'required|string|max:255',
            'travel_legs.*.date' => 'required|date',
            'travel_legs.*.distance' => 'required|numeric|min:0',
            'purpose' => 'required|string|max:500',
            'total_distance' => 'required|numeric|min:0',
            'rate_per_km' => 'required|numeric|min:0',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|mimes:jpg,jpeg,png,pdf|max:10240',
            'save_as_draft' => 'boolean', // Add this field
        ]);

        // Calculate total cost
        $totalCost = $validated['total_distance'] * $validated['rate_per_km'];

        // Get first and last travel dates for the main record
        $travelDates = collect($validated['travel_legs'])->pluck('date')->sort();
        $firstTravelDate = $travelDates->first();
        $lastTravelDate = $travelDates->last();

        // Determine status
        $status = $request->boolean('save_as_draft') ? 'draft' : 'submitted';

        // Create the travel claim
        $travelClaim = TravelClaim::create([
            'user_id' => Auth::id(),
            'vehicle_type' => $validated['vehicle_type'],
            'registration_plate_number' => $validated['registration_plate_number'],
            'cubic_capacity' => $validated['cubic_capacity'],
            'date_of_travel' => $firstTravelDate,
            'end_date_of_travel' => $validated['is_multiple_legs'] ? $lastTravelDate : null,
            'is_multiple_days' => $validated['is_multiple_legs'],
            'travel_from' => $validated['travel_legs'][0]['from'],
            'travel_to' => $validated['travel_legs'][count($validated['travel_legs']) - 1]['to'],
            'purpose' => $validated['purpose'],
            'total_distance' => $validated['total_distance'],
            'rate_per_km' => $validated['rate_per_km'],
            'total_cost' => $totalCost,
            'status' => $status,
            'claim_date' => now(),
            'travel_legs_data' => $validated['travel_legs'],
        ]);

        // Handle file uploads
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $travelClaim->addMedia($file)->toMediaCollection('attachments');
            }
        }

        $message = $status === 'draft'
            ? 'Travel claim saved as draft successfully!'
            : 'Travel claim submitted successfully!';

        return redirect()->route('user.request-claim.index')
            ->with('success', $message);
    }

    /**
     * Display the specified resource.
     */
    public function show(TravelClaim $travelClaim)
    {
        // Authorization check - user can only view their own claims
        if ($travelClaim->user_id !== Auth::id()) {
            abort(403);
        }

        $travelClaim->load(['media', 'user.approver']); // Load user and approver relationship

        // Store rate_per_km in a variable to use in the closure
        $ratePerKm = $travelClaim->rate_per_km;

        return Inertia::render('User/TravelClaim/Show', [
            'travelClaim' => $travelClaim->toArray() + [
                'documents' => $travelClaim->media->map(function ($media) {
                    return [
                        'name' => $media->file_name,
                        'url' => $media->getUrl(),
                        'size' => $media->size,
                        'type' => $media->mime_type,
                    ];
                })->toArray(),
                'expenses' => $travelClaim->travel_legs_data ? collect($travelClaim->travel_legs_data)->map(function ($leg) use ($ratePerKm) {
                    return [
                        'description' => "Travel from {$leg['from']} to {$leg['to']}",
                        'amount' => $leg['distance'] * $ratePerKm,
                        'date' => $leg['date'],
                    ];
                })->toArray() : [],
                'user' => [
                    'name' => $travelClaim->user->name,
                    'department' => $travelClaim->user->department,
                    'position' => $travelClaim->user->job_title,
                    'approver' => $travelClaim->user->approver ? [
                        'name' => $travelClaim->user->approver->name,
                        'email' => $travelClaim->user->approver->email,
                    ] : null,
                ],
            ],
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(TravelClaim $travelClaim)
    {
        // Authorization check - user can only edit their own draft/pending claims
        if ($travelClaim->user_id !== Auth::id() || ! in_array($travelClaim->status, ['draft', 'pending'])) {
            abort(403);
        }

        $travelClaim->load('media');

        return Inertia::render('User/TravelClaim/Edit', [
            'travelClaim' => $travelClaim->toArray() + [
                'travel_legs' => $travelClaim->travel_legs_data ?? [],
                'documents' => $travelClaim->media->map(function ($media) {
                    return [
                        'name' => $media->file_name,
                        'url' => $media->getUrl(),
                        'size' => $media->size,
                        'type' => $media->mime_type,
                    ];
                })->toArray(),
            ],
            'defaultRates' => [
                'car' => 0.75,
                'motorcycle' => 0.45,
            ],
            'vehicleTypes' => [
                ['value' => 'car', 'label' => 'Car'],
                ['value' => 'motorcycle', 'label' => 'Motorcycle'],
            ],
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, TravelClaim $travelClaim)
    {
        // Authorization check
        if ($travelClaim->user_id !== Auth::id() || ! in_array($travelClaim->status, ['draft', 'pending'])) {
            abort(403);
        }

        // If this is just a status update (submitting for approval), handle it differently
        if ($request->boolean('submit_claim')) {
            $travelClaim->update([
                'status' => 'submitted',
                'submitted_at' => now(),
            ]);

            return redirect()->route('user.travel-claims.show', $travelClaim)
                ->with('success', 'Travel claim submitted for approval successfully!');
        }

        // If this is a regular update with form data
        $validated = $request->validate([
            'vehicle_type' => 'required|in:car,motorcycle,bicycle,other',
            'registration_plate_number' => 'nullable|string|max:20',
            'cubic_capacity' => 'nullable|integer|min:0',
            'is_multiple_legs' => 'boolean',
            'travel_legs' => 'required|array|min:1',
            'travel_legs.*.from' => 'required|string|max:255',
            'travel_legs.*.to' => 'required|string|max:255',
            'travel_legs.*.date' => 'required|date',
            'travel_legs.*.distance' => 'required|numeric|min:0',
            'purpose' => 'required|string|max:500',
            'total_distance' => 'required|numeric|min:0',
            'rate_per_km' => 'required|numeric|min:0',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|mimes:jpg,jpeg,png,pdf|max:10240',
            'save_as_draft' => 'boolean',
        ]);

        // Calculate total cost
        $totalCost = $validated['total_distance'] * $validated['rate_per_km'];

        // Get first and last travel dates for the main record
        $travelDates = collect($validated['travel_legs'])->pluck('date')->sort();
        $firstTravelDate = $travelDates->first();
        $lastTravelDate = $travelDates->last();

        // Determine status
        $status = $request->boolean('save_as_draft') ? 'draft' : 'submitted';

        $travelClaim->update([
            'vehicle_type' => $validated['vehicle_type'],
            'registration_plate_number' => $validated['registration_plate_number'],
            'cubic_capacity' => $validated['cubic_capacity'],
            'date_of_travel' => $firstTravelDate,
            'end_date_of_travel' => $validated['is_multiple_legs'] ? $lastTravelDate : null,
            'is_multiple_days' => $validated['is_multiple_legs'],
            'travel_from' => $validated['travel_legs'][0]['from'],
            'travel_to' => $validated['travel_legs'][count($validated['travel_legs']) - 1]['to'],
            'purpose' => $validated['purpose'],
            'total_distance' => $validated['total_distance'],
            'rate_per_km' => $validated['rate_per_km'],
            'total_cost' => $totalCost,
            'status' => $status,
            'travel_legs_data' => $validated['travel_legs'],
        ]);

        // Handle file uploads
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $travelClaim->addMedia($file)->toMediaCollection('attachments');
            }
        }

        $message = $status === 'draft'
            ? 'Travel claim saved as draft successfully!'
            : 'Travel claim updated successfully!';

        return redirect()->route('user.travel-claims.show', $travelClaim)
            ->with('success', $message);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TravelClaim $travelClaim)
    {
        // Authorization check - user can only delete their own draft claims
        if ($travelClaim->user_id !== Auth::id() || $travelClaim->status !== 'draft') {
            abort(403);
        }

        $travelClaim->delete();

        return redirect()->route('user.request-claim.index')
            ->with('success', 'Travel claim deleted successfully!');
    }

    /**
     * Submit a draft claim for approval
     */
    public function submit(Request $request, TravelClaim $travelClaim)
    {
        // Authorization check - user can only submit their own draft claims
        if ($travelClaim->user_id !== Auth::id() || $travelClaim->status !== 'draft') {
            abort(403);
        }

        // Get the user's assigned approver
        $user = Auth::user();
        $approver = $user->approver;

        // Check if user has an approver assigned
        if (! $approver) {
            return redirect()->back()
                ->with('error', 'No approver assigned to your account. Please contact administrator.');
        }

        $travelClaim->update([
            'approver_id' => $approver->id, // Set the approver from user's approver_id
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        return redirect()->route('user.travel-claims.show', $travelClaim)
            ->with('success', 'Travel claim submitted for approval successfully!');
    }
}
