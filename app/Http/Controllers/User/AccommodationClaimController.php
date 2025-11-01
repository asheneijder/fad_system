<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Jobs\SendClaimSubmittedNotification;
use App\Models\AccommodationClaim;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AccommodationClaimController extends Controller
{
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $user = auth()->user();
        
        // If you need all distinct job titles for something else
        $allJobTitles = User::distinct()->whereNotNull('job_title')->pluck('job_title');
        
        $maxRate = $this->getMaxRateByJobTitle($user->job_title);
        
        return Inertia::render('User/AccommodationClaim/Create', [
            'currencies' => AccommodationClaim::getCurrencies(),
            'userJobTitle' => $user->job_title,
            'allJobTitles' => $allJobTitles, // Optional: if you need it in frontend
            'defaultRates' => [
                'MYR' => ['max_rate' => $maxRate, 'tax_percentage' => 6.00, 'service_charge_percentage' => 10.00],
                'USD' => ['max_rate' => $maxRate * 0.21, 'tax_percentage' => 10.00, 'service_charge_percentage' => 15.00],
                'SGD' => ['max_rate' => $maxRate * 0.29, 'tax_percentage' => 7.00, 'service_charge_percentage' => 10.00],
            ],
        ]);
    }

    /**
     * Get max rate based on job title
     */
    private function getMaxRateByJobTitle($jobTitle)
    {
        $rates = [
            'CHIEF EXECUTIVE OFFICER' => 500.00,
            'ASSISTANT GENERAL MANAGER' => 500.00,
            'CHIEF COMPLIANCE & GOVERNANCE OFFICER' => 500.00,
            'SENIOR MANAGER' => 500.00,
            'MANAGER' => 500.00,
            'ASSISTANT MANAGER' => 500.00,
            'SENIOR EXECUTIVE' => 400.00,
            'EXECUTIVE' => 300.00,
            'SENIOR CLERK' => 250.00,
            'CLERK' => 200.00,
            'GRADUATEE TRAINEE' => 150.00,
        ];
        
        return $rates[$jobTitle] ?? 300.00; // Default to 300 if job title not found
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'hotel_name' => 'required|string|max:255',
            'check_in_date' => 'required|date',
            'check_out_date' => 'required|date|after:check_in_date',
            'number_of_nights' => 'required|integer|min:1',
            'rate_per_night' => 'required|numeric|min:0',
            'currency' => 'required|in:MYR,USD,SGD',
            'tax_percentage' => 'required|numeric|min:0|max:100',
            'service_charge_percentage' => 'required|numeric|min:0|max:100',
            'tax_amount' => 'required|numeric|min:0',
            'service_charge_amount' => 'required|numeric|min:0',
            'subtotal_amount' => 'required|numeric|min:0',
            'total_amount' => 'required|numeric|min:0',
            'purpose' => 'required|string|max:500',
            'destination_city' => 'required|string|max:255',
            'destination_country' => 'required|string|max:255',
            'remarks' => 'nullable|string|max:1000',
            'hotel_receipts' => 'nullable|array',
            'hotel_receipts.*' => 'file|mimes:jpg,jpeg,png,pdf|max:10240',
            'supporting_documents' => 'nullable|array',
            'supporting_documents.*' => 'file|mimes:jpg,jpeg,png,pdf|max:10240',
            'save_as_draft' => 'boolean',
        ]);

        // Determine status and approver
        $status = $request->boolean('save_as_draft') ? AccommodationClaim::STATUS_DRAFT : AccommodationClaim::STATUS_SUBMITTED;
        $approverId = null;

        // If submitting (not draft), get the approver
        if ($status === AccommodationClaim::STATUS_SUBMITTED) {
            $user = Auth::user();
            $approver = $user->approver;

            // Check if user has an approver assigned
            if (! $approver) {
                return redirect()->back()
                    ->with('error', 'No approver assigned to your account. Please contact administrator.')
                    ->withInput();
            }

            $approverId = $approver->id;
        }

        // Create the accommodation claim
        $accommodationClaim = AccommodationClaim::create([
            'user_id' => Auth::id(),
            'approver_id' => $approverId, // Set approver_id if submitted
            'hotel_name' => $validated['hotel_name'],
            'check_in_date' => $validated['check_in_date'],
            'check_out_date' => $validated['check_out_date'],
            'number_of_nights' => $validated['number_of_nights'],
            'rate_per_night' => $validated['rate_per_night'],
            'currency' => $validated['currency'],
            'tax_percentage' => $validated['tax_percentage'],
            'service_charge_percentage' => $validated['service_charge_percentage'],
            'tax_amount' => $validated['tax_amount'],
            'service_charge_amount' => $validated['service_charge_amount'],
            'subtotal_amount' => $validated['subtotal_amount'],
            'total_amount' => $validated['total_amount'],
            'purpose' => $validated['purpose'],
            'destination_city' => $validated['destination_city'],
            'destination_country' => $validated['destination_country'],
            'remarks' => $validated['remarks'] ?? null,
            'status' => $status,
        ]);

        // Handle file uploads
        if ($request->hasFile('hotel_receipts')) {
            foreach ($request->file('hotel_receipts') as $file) {
                $accommodationClaim->addMedia($file)->toMediaCollection('hotel_receipts');
            }
        }

        if ($request->hasFile('supporting_documents')) {
            foreach ($request->file('supporting_documents') as $file) {
                $accommodationClaim->addMedia($file)->toMediaCollection('supporting_documents');
            }
        }

        // Send notification to approver if submitted (not draft)
        if ($status === AccommodationClaim::STATUS_SUBMITTED) {
            dispatch(new SendClaimSubmittedNotification($accommodationClaim, 'accommodation', Auth::user()));
        }

        $message = $accommodationClaim->status === AccommodationClaim::STATUS_DRAFT
            ? 'Accommodation claim saved as draft successfully!'
            : 'Accommodation claim submitted successfully!';

        return redirect()->route('user.request-claim.index')
            ->with('success', $message);
    }

    /**
     * Display the specified resource.
     */
    public function show(AccommodationClaim $accommodationClaim)
    {
        // Authorization check - user can only view their own claims
        if ($accommodationClaim->user_id !== Auth::id()) {
            abort(403);
        }

        $accommodationClaim->load(['media', 'user.approver']);

        // Ensure we always return arrays for documents, even if empty
        $hotelReceipts = $accommodationClaim->media
            ->where('collection_name', 'hotel_receipts')
            ->map(function ($media) {
                return [
                    'name' => $media->file_name,
                    'url' => $media->getUrl(),
                    'size' => $media->size,
                    'type' => $media->mime_type,
                ];
            })->values()->toArray(); // Use values() to reset keys and ensure it's an array

        $supportingDocuments = $accommodationClaim->media
            ->where('collection_name', 'supporting_documents')
            ->map(function ($media) {
                return [
                    'name' => $media->file_name,
                    'url' => $media->getUrl(),
                    'size' => $media->size,
                    'type' => $media->mime_type,
                ];
            })->values()->toArray(); // Use values() to reset keys and ensure it's an array

        return Inertia::render('User/AccommodationClaim/Show', [
            'accommodationClaim' => array_merge($accommodationClaim->toArray(), [
                'hotel_receipts' => $hotelReceipts,
                'supporting_documents' => $supportingDocuments,
                'user' => [
                    'name' => $accommodationClaim->user->name,
                    'department' => $accommodationClaim->user->department,
                    'position' => $accommodationClaim->user->job_title,
                    'employee_id' => $accommodationClaim->user->employee_id, // Add this if needed
                    'approver' => $accommodationClaim->user->approver ? [
                        'name' => $accommodationClaim->user->approver->name,
                        'email' => $accommodationClaim->user->approver->email,
                    ] : null,
                ],
                'currency_display' => $accommodationClaim->currency_display,
                'status_display' => $accommodationClaim->status_display,
                'stay_period' => $accommodationClaim->stay_period,
            ]),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(AccommodationClaim $accommodationClaim)
    {
        // Authorization check - user can only edit their own draft claims
        if ($accommodationClaim->user_id !== Auth::id() || ! in_array($accommodationClaim->status, [AccommodationClaim::STATUS_DRAFT])) {
            abort(403);
        }

        $accommodationClaim->load('media');

        return Inertia::render('User/AccommodationClaim/Edit', [
            'accommodationClaim' => $accommodationClaim->toArray() + [
                'hotel_receipts' => $accommodationClaim->media->where('collection_name', 'hotel_receipts')->map(function ($media) {
                    return [
                        'name' => $media->file_name,
                        'url' => $media->getUrl(),
                        'size' => $media->size,
                        'type' => $media->mime_type,
                    ];
                })->toArray(),
                'supporting_documents' => $accommodationClaim->media->where('collection_name', 'supporting_documents')->map(function ($media) {
                    return [
                        'name' => $media->file_name,
                        'url' => $media->getUrl(),
                        'size' => $media->size,
                        'type' => $media->mime_type,
                    ];
                })->toArray(),
            ],
            // FIX: Ensure currencies are in correct format for PrimeVue Select
            'currencies' => [
                ['label' => 'MYR - Malaysian Ringgit', 'value' => 'MYR'],
                ['label' => 'USD - US Dollar', 'value' => 'USD'],
                ['label' => 'SGD - Singapore Dollar', 'value' => 'SGD'],
            ],
            'defaultRates' => [
                'MYR' => [
                    'max_rate' => 300.00,
                    'tax_percentage' => 6.00,
                    'service_charge_percentage' => 10.00,
                ],
                'USD' => [
                    'max_rate' => 100.00,
                    'tax_percentage' => 10.00,
                    'service_charge_percentage' => 15.00,
                ],
                'SGD' => [
                    'max_rate' => 150.00,
                    'tax_percentage' => 7.00,
                    'service_charge_percentage' => 10.00,
                ],
            ],
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, AccommodationClaim $accommodationClaim)
    {
        // Authorization check
        if ($accommodationClaim->user_id !== Auth::id() || ! in_array($accommodationClaim->status, [AccommodationClaim::STATUS_DRAFT])) {
            abort(403);
        }

        $validated = $request->validate([
            'hotel_name' => 'required|string|max:255',
            'check_in_date' => 'required|date',
            'check_out_date' => 'required|date|after:check_in_date',
            'number_of_nights' => 'required|integer|min:1',
            'rate_per_night' => 'required|numeric|min:0',
            'currency' => 'required|in:MYR,USD,SGD',
            'tax_percentage' => 'required|numeric|min:0|max:100',
            'service_charge_percentage' => 'required|numeric|min:0|max:100',
            'tax_amount' => 'required|numeric|min:0',
            'service_charge_amount' => 'required|numeric|min:0',
            'subtotal_amount' => 'required|numeric|min:0',
            'total_amount' => 'required|numeric|min:0',
            'purpose' => 'required|string|max:500',
            'destination_city' => 'required|string|max:255',
            'destination_country' => 'required|string|max:255',
            'remarks' => 'nullable|string|max:1000',
            'hotel_receipts' => 'nullable|array',
            'hotel_receipts.*' => 'file|mimes:jpg,jpeg,png,pdf|max:10240',
            'supporting_documents' => 'nullable|array',
            'supporting_documents.*' => 'file|mimes:jpg,jpeg,png,pdf|max:10240',
            'removed_hotel_receipts' => 'nullable|array',
            'removed_supporting_documents' => 'nullable|array',
            'save_as_draft' => 'boolean',
        ]);

        // Update the accommodation claim
        $accommodationClaim->update([
            'hotel_name' => $validated['hotel_name'],
            'check_in_date' => $validated['check_in_date'],
            'check_out_date' => $validated['check_out_date'],
            'number_of_nights' => $validated['number_of_nights'],
            'rate_per_night' => $validated['rate_per_night'],
            'currency' => $validated['currency'],
            'tax_percentage' => $validated['tax_percentage'],
            'service_charge_percentage' => $validated['service_charge_percentage'],
            'tax_amount' => $validated['tax_amount'],
            'service_charge_amount' => $validated['service_charge_amount'],
            'subtotal_amount' => $validated['subtotal_amount'],
            'total_amount' => $validated['total_amount'],
            'purpose' => $validated['purpose'],
            'destination_city' => $validated['destination_city'],
            'destination_country' => $validated['destination_country'],
            'remarks' => $validated['remarks'] ?? null,
            'status' => $request->boolean('save_as_draft') ? AccommodationClaim::STATUS_DRAFT : AccommodationClaim::STATUS_SUBMITTED,
        ]);

        // Handle removed files
        if ($request->has('removed_hotel_receipts')) {
            foreach ($request->removed_hotel_receipts as $filename) {
                $media = $accommodationClaim->media->where('file_name', $filename)->first();
                if ($media) {
                    $media->delete();
                }
            }
        }

        if ($request->has('removed_supporting_documents')) {
            foreach ($request->removed_supporting_documents as $filename) {
                $media = $accommodationClaim->media->where('file_name', $filename)->first();
                if ($media) {
                    $media->delete();
                }
            }
        }

        // Handle new file uploads
        if ($request->hasFile('hotel_receipts')) {
            foreach ($request->file('hotel_receipts') as $file) {
                $accommodationClaim->addMedia($file)->toMediaCollection('hotel_receipts');
            }
        }

        if ($request->hasFile('supporting_documents')) {
            foreach ($request->file('supporting_documents') as $file) {
                $accommodationClaim->addMedia($file)->toMediaCollection('supporting_documents');
            }
        }

        $message = $accommodationClaim->status === AccommodationClaim::STATUS_DRAFT
            ? 'Accommodation claim saved as draft successfully!'
            : 'Accommodation claim updated successfully!';

        return redirect()->route('user.accommodation-claims.show', $accommodationClaim)
            ->with('success', $message);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(AccommodationClaim $accommodationClaim)
    {
        // Authorization check - user can only delete their own draft claims
        if ($accommodationClaim->user_id !== Auth::id() || $accommodationClaim->status !== AccommodationClaim::STATUS_DRAFT) {
            abort(403);
        }

        $accommodationClaim->delete();

        return redirect()->route('user.request-claim.index')
            ->with('success', 'Accommodation claim deleted successfully!');
    }

    /**
     * Submit a draft claim for approval
     */
    public function submit(Request $request, AccommodationClaim $accommodationClaim)
    {
        // Authorization check - user can only submit their own draft claims
        if ($accommodationClaim->user_id !== Auth::id() || $accommodationClaim->status !== AccommodationClaim::STATUS_DRAFT) {
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

        $accommodationClaim->update([
            'approver_id' => $approver->id,
            'status' => AccommodationClaim::STATUS_SUBMITTED,
        ]);

        // Send notification to approver
        dispatch(new SendClaimSubmittedNotification($accommodationClaim, 'accommodation', Auth::user()));

        return redirect()->route('user.accommodation-claims.show', $accommodationClaim)
            ->with('success', 'Accommodation claim submitted for approval successfully!');
    }
}
