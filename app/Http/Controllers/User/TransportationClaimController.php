<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\TransportationClaim;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class TransportationClaimController extends Controller
{
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('User/TransportationClaim/Create', [
            'transportTypes' => collect(TransportationClaim::getTransportTypes())->map(function ($label, $value) {
                return ['label' => $label, 'value' => $value];
            })->values()->toArray(),
            'tripTypes' => collect(TransportationClaim::getTripTypes())->map(function ($label, $value) {
                return ['label' => $label, 'value' => $value];
            })->values()->toArray(),
            'currencies' => collect(TransportationClaim::getCurrencies())->map(function ($label, $value) {
                return ['label' => $label, 'value' => $value];
            })->values()->toArray(),
            'defaultRates' => TransportationClaim::getDefaultRates(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'claim_date' => 'required|date',
            'transport_type' => 'required|in:'.implode(',', array_keys(TransportationClaim::getTransportTypes())),
            'purpose' => 'required|string|max:500',
            'from_location' => 'required|string|max:255',
            'to_location' => 'required|string|max:255',
            'distance_km' => 'nullable|numeric|min:0',
            'rate_per_km' => 'nullable|numeric|min:0',
            'amount' => 'required|numeric|min:0',
            'currency' => 'required|in:'.implode(',', array_keys(TransportationClaim::getCurrencies())),
            'trip_type' => 'required|in:'.implode(',', array_keys(TransportationClaim::getTripTypes())),
            'number_of_trips' => 'required|integer|min:1',
            'receipt_number' => 'nullable|string|max:100',
            'remarks' => 'nullable|string|max:1000',
            'transport_receipts' => 'nullable|array',
            'transport_receipts.*' => 'file|mimes:jpg,jpeg,png,pdf|max:10240',
            'supporting_documents' => 'nullable|array',
            'supporting_documents.*' => 'file|mimes:jpg,jpeg,png,pdf|max:10240',
            'save_as_draft' => 'boolean',
        ]);

        $transportationClaim = TransportationClaim::create([
            'user_id' => Auth::id(),
            'claim_date' => $validated['claim_date'],
            'transport_type' => $validated['transport_type'],
            'purpose' => $validated['purpose'],
            'from_location' => $validated['from_location'],
            'to_location' => $validated['to_location'],
            'distance_km' => $validated['distance_km'] ?? 0,
            'rate_per_km' => $validated['rate_per_km'] ?? 0,
            'amount' => $validated['amount'],
            'currency' => $validated['currency'],
            'trip_type' => $validated['trip_type'],
            'number_of_trips' => $validated['number_of_trips'],
            'receipt_number' => $validated['receipt_number'] ?? null,
            'remarks' => $validated['remarks'] ?? null,
            'status' => $request->boolean('save_as_draft') ? TransportationClaim::STATUS_DRAFT : TransportationClaim::STATUS_SUBMITTED,
        ]);

        // Handle file uploads
        if ($request->hasFile('transport_receipts')) {
            foreach ($request->file('transport_receipts') as $file) {
                $transportationClaim->addMedia($file)->toMediaCollection('transport_receipts');
            }
        }

        if ($request->hasFile('supporting_documents')) {
            foreach ($request->file('supporting_documents') as $file) {
                $transportationClaim->addMedia($file)->toMediaCollection('supporting_documents');
            }
        }

        $message = $transportationClaim->status === TransportationClaim::STATUS_DRAFT
            ? 'Transportation claim saved as draft successfully!'
            : 'Transportation claim submitted successfully!';

        return redirect()->route('user.transportation-claims.show', $transportationClaim)
            ->with('success', $message);
    }

    /**
     * Display the specified resource.
     */
    public function show(TransportationClaim $transportationClaim)
    {
        // Authorization check - user can only view their own claims
        if ($transportationClaim->user_id !== Auth::id()) {
            abort(403);
        }

        $transportationClaim->load('media');

        return Inertia::render('User/TransportationClaim/Show', [
            'transportationClaim' => $transportationClaim->toArray() + [
                'transport_receipts' => $transportationClaim->media->where('collection_name', 'transport_receipts')->map(function ($media) {
                    return [
                        'name' => $media->file_name,
                        'url' => $media->getUrl(),
                        'size' => $media->size,
                        'type' => $media->mime_type,
                    ];
                })->toArray(),
                'supporting_documents' => $transportationClaim->media->where('collection_name', 'supporting_documents')->map(function ($media) {
                    return [
                        'name' => $media->file_name,
                        'url' => $media->getUrl(),
                        'size' => $media->size,
                        'type' => $media->mime_type,
                    ];
                })->toArray(),
                'transport_type_display' => $transportationClaim->transport_type_display,
                'trip_type_display' => $transportationClaim->trip_type_display,
                'currency_display' => $transportationClaim->currency_display,
                'status_display' => $transportationClaim->status_display,
                'route' => $transportationClaim->route,
            ],
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(TransportationClaim $transportationClaim)
    {
        // Authorization check - user can only edit their own draft claims
        if ($transportationClaim->user_id !== Auth::id() || ! in_array($transportationClaim->status, [TransportationClaim::STATUS_DRAFT])) {
            abort(403);
        }

        $transportationClaim->load('media');

        return Inertia::render('User/TransportationClaim/Edit', [
            'transportationClaim' => $transportationClaim->toArray() + [
                'transport_receipts' => $transportationClaim->media->where('collection_name', 'transport_receipts')->map(function ($media) {
                    return [
                        'name' => $media->file_name,
                        'url' => $media->getUrl(),
                        'size' => $media->size,
                        'type' => $media->mime_type,
                    ];
                })->toArray(),
                'supporting_documents' => $transportationClaim->media->where('collection_name', 'supporting_documents')->map(function ($media) {
                    return [
                        'name' => $media->file_name,
                        'url' => $media->getUrl(),
                        'size' => $media->size,
                        'type' => $media->mime_type,
                    ];
                })->toArray(),
            ],
            'transportTypes' => collect(TransportationClaim::getTransportTypes())->map(function ($label, $value) {
                return ['label' => $label, 'value' => $value];
            })->values()->toArray(),
            'tripTypes' => collect(TransportationClaim::getTripTypes())->map(function ($label, $value) {
                return ['label' => $label, 'value' => $value];
            })->values()->toArray(),
            'currencies' => collect(TransportationClaim::getCurrencies())->map(function ($label, $value) {
                return ['label' => $label, 'value' => $value];
            })->values()->toArray(),
            'defaultRates' => TransportationClaim::getDefaultRates(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, TransportationClaim $transportationClaim)
    {
        // Authorization check
        if ($transportationClaim->user_id !== Auth::id() || ! in_array($transportationClaim->status, [TransportationClaim::STATUS_DRAFT])) {
            abort(403);
        }

        $validated = $request->validate([
            'claim_date' => 'required|date',
            'transport_type' => 'required|in:'.implode(',', array_keys(TransportationClaim::getTransportTypes())),
            'purpose' => 'required|string|max:500',
            'from_location' => 'required|string|max:255',
            'to_location' => 'required|string|max:255',
            'distance_km' => 'nullable|numeric|min:0',
            'rate_per_km' => 'nullable|numeric|min:0',
            'amount' => 'required|numeric|min:0',
            'currency' => 'required|in:'.implode(',', array_keys(TransportationClaim::getCurrencies())),
            'trip_type' => 'required|in:'.implode(',', array_keys(TransportationClaim::getTripTypes())),
            'number_of_trips' => 'required|integer|min:1',
            'receipt_number' => 'nullable|string|max:100',
            'remarks' => 'nullable|string|max:1000',
            'transport_receipts' => 'nullable|array',
            'transport_receipts.*' => 'file|mimes:jpg,jpeg,png,pdf|max:10240',
            'supporting_documents' => 'nullable|array',
            'supporting_documents.*' => 'file|mimes:jpg,jpeg,png,pdf|max:10240',
            'removed_transport_receipts' => 'nullable|array',
            'removed_supporting_documents' => 'nullable|array',
            'save_as_draft' => 'boolean',
        ]);

        // Update the transportation claim
        $transportationClaim->update([
            'claim_date' => $validated['claim_date'],
            'transport_type' => $validated['transport_type'],
            'purpose' => $validated['purpose'],
            'from_location' => $validated['from_location'],
            'to_location' => $validated['to_location'],
            'distance_km' => $validated['distance_km'] ?? 0,
            'rate_per_km' => $validated['rate_per_km'] ?? 0,
            'amount' => $validated['amount'],
            'currency' => $validated['currency'],
            'trip_type' => $validated['trip_type'],
            'number_of_trips' => $validated['number_of_trips'],
            'receipt_number' => $validated['receipt_number'] ?? null,
            'remarks' => $validated['remarks'] ?? null,
            'status' => $request->boolean('save_as_draft') ? TransportationClaim::STATUS_DRAFT : TransportationClaim::STATUS_SUBMITTED,
        ]);

        // Handle removed files
        if ($request->has('removed_transport_receipts')) {
            foreach ($request->removed_transport_receipts as $filename) {
                $media = $transportationClaim->media->where('file_name', $filename)->first();
                if ($media) {
                    $media->delete();
                }
            }
        }

        if ($request->has('removed_supporting_documents')) {
            foreach ($request->removed_supporting_documents as $filename) {
                $media = $transportationClaim->media->where('file_name', $filename)->first();
                if ($media) {
                    $media->delete();
                }
            }
        }

        // Handle new file uploads
        if ($request->hasFile('transport_receipts')) {
            foreach ($request->file('transport_receipts') as $file) {
                $transportationClaim->addMedia($file)->toMediaCollection('transport_receipts');
            }
        }

        if ($request->hasFile('supporting_documents')) {
            foreach ($request->file('supporting_documents') as $file) {
                $transportationClaim->addMedia($file)->toMediaCollection('supporting_documents');
            }
        }

        $message = $transportationClaim->status === TransportationClaim::STATUS_DRAFT
            ? 'Transportation claim saved as draft successfully!'
            : 'Transportation claim updated successfully!';

        return redirect()->route('user.transportation-claims.show', $transportationClaim)
            ->with('success', $message);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TransportationClaim $transportationClaim)
    {
        // Authorization check - user can only delete their own draft claims
        if ($transportationClaim->user_id !== Auth::id() || ! in_array($transportationClaim->status, [TransportationClaim::STATUS_DRAFT])) {
            abort(403);
        }

        $transportationClaim->delete();

        return redirect()->route('user.request-claim.index')
            ->with('success', 'Transportation claim deleted successfully!');
    }

    /**
     * Submit claim for approval.
     */
    public function submit(TransportationClaim $transportationClaim)
    {
        // Authorization check - user can only submit their own draft claims
        if ($transportationClaim->user_id !== Auth::id() || $transportationClaim->status !== TransportationClaim::STATUS_DRAFT) {
            abort(403);
        }

        $transportationClaim->update([
            'status' => TransportationClaim::STATUS_SUBMITTED,
        ]);

        return redirect()->route('user.transportation-claims.show', $transportationClaim)
            ->with('success', 'Transportation claim submitted for approval successfully!');
    }
}
