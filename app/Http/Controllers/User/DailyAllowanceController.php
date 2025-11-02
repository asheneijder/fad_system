<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Jobs\SendClaimSubmittedNotification;
use App\Models\DailyAllowance;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DailyAllowanceController extends Controller
{
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('User/DailyAllowance/Create', [
            'allowanceTypes' => DailyAllowance::getAllowanceTypes(),
            'currencies' => DailyAllowance::getCurrencies(),
            'defaultRates' => [
                'MYR' => [
                    'full_day' => 100.00,
                    'breakfast' => 20.00,
                    'lunch' => 40.00,
                    'dinner' => 40.00,
                ],
                'USD' => [
                    'full_day' => 25.00,
                    'breakfast' => 5.00,
                    'lunch' => 10.00,
                    'dinner' => 10.00,
                ],
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'claim_date' => 'required|date',
            'allowance_type' => 'required|in:full_day,breakfast,lunch,dinner',
            'currency' => 'required|in:MYR,USD',
            'daily_rate' => 'required|numeric|min:0',
            'purpose' => 'required|string|max:500',
            'destination' => 'required|string|max:255',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|mimes:jpg,jpeg,png,pdf|max:10240',
            'save_as_draft' => 'boolean',
        ]);

        // Calculate claim amount based on allowance type
        $claimPercentage = DailyAllowance::getPercentageByType($validated['allowance_type']);
        $claimAmount = ($validated['daily_rate'] * $claimPercentage) / 100;

        // Determine status and approver
        $status = $request->boolean('save_as_draft') ? 'draft' : 'submitted';
        $approverId = null;

        // If submitting (not draft), get the approver
        if ($status === 'submitted') {
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

        // Create the daily allowance claim
        $dailyAllowance = DailyAllowance::create([
            'user_id' => Auth::id(),
            'approver_id' => $approverId, // Set approver_id if submitted
            'claim_date' => $validated['claim_date'],
            'allowance_type' => $validated['allowance_type'],
            'currency' => $validated['currency'],
            'daily_rate' => (float) $validated['daily_rate'], // Ensure float type
            'claim_percentage' => $claimPercentage,
            'claim_amount' => (float) $claimAmount, // Ensure float type
            'purpose' => $validated['purpose'],
            'destination' => $validated['destination'],
            'status' => $status,
        ]);

        // Handle file uploads
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $dailyAllowance->addMedia($file)->toMediaCollection('receipts');
            }
        }

        // Send notification to approver if submitted (not draft)
        if ($status === 'submitted') {
            dispatch(new SendClaimSubmittedNotification($dailyAllowance, 'daily', Auth::user()));
        }

        $message = $status === 'draft'
            ? 'Daily allowance claim saved as draft successfully!'
            : 'Daily allowance claim submitted successfully!';

        return redirect()->route('user.request-claim.index')
            ->with('success', $message);
    }

    /**
     * Display the specified resource.
     */
    public function show(DailyAllowance $dailyAllowance)
    {
        // Authorization check - user can only view their own claims
        if ($dailyAllowance->user_id !== Auth::id()) {
            abort(403);
        }

        $dailyAllowance->load(['media', 'user.approver']);

        // Ensure numeric values are properly cast
        $dailyAllowanceData = array_merge($dailyAllowance->toArray(), [
            'daily_rate' => (float) $dailyAllowance->daily_rate,
            'claim_amount' => (float) $dailyAllowance->claim_amount,
            'documents' => $dailyAllowance->media->map(function ($media) {
                return [
                    'name' => $media->file_name,
                    'url' => $media->getUrl(),
                    'size' => $media->size,
                    'type' => $media->mime_type,
                ];
            })->toArray(),
            'user' => [
                'name' => $dailyAllowance->user->name,
                'department' => $dailyAllowance->user->department,
                'position' => $dailyAllowance->user->job_title,
                'approver' => $dailyAllowance->user->approver ? [
                    'name' => $dailyAllowance->user->approver->name,
                    'email' => $dailyAllowance->user->approver->email,
                ] : null,
            ],
            'allowance_type_display' => $dailyAllowance->allowance_type_display,
            'currency_display' => $dailyAllowance->currency_display,
        ]);

        return Inertia::render('User/DailyAllowance/Show', [
            'dailyAllowance' => $dailyAllowanceData,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(DailyAllowance $dailyAllowance)
    {
        // Authorization check - user can only edit their own draft claims
        if ($dailyAllowance->user_id !== Auth::id() || ! in_array($dailyAllowance->status, ['draft'])) {
            abort(403);
        }

        $dailyAllowance->load('media');

        // Ensure numeric values are properly cast
        $dailyAllowanceData = array_merge($dailyAllowance->toArray(), [
            'daily_rate' => (float) $dailyAllowance->daily_rate,
            'claim_amount' => (float) $dailyAllowance->claim_amount,
            'documents' => $dailyAllowance->media->map(function ($media) {
                return [
                    'name' => $media->file_name,
                    'url' => $media->getUrl(),
                    'size' => $media->size,
                    'type' => $media->mime_type,
                ];
            })->toArray(),
        ]);

        return Inertia::render('User/DailyAllowance/Edit', [
            'dailyAllowance' => $dailyAllowanceData,
            'allowanceTypes' => DailyAllowance::getAllowanceTypes(),
            'currencies' => DailyAllowance::getCurrencies(),
            'defaultRates' => [
                'MYR' => [
                    'full_day' => 100.00,
                    'breakfast' => 20.00,
                    'lunch' => 40.00,
                    'dinner' => 40.00,
                ],
                'USD' => [
                    'full_day' => 25.00,
                    'breakfast' => 5.00,
                    'lunch' => 10.00,
                    'dinner' => 10.00,
                ],
            ],
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, DailyAllowance $dailyAllowance)
    {
        // Authorization check
        if ($dailyAllowance->user_id !== Auth::id() || ! in_array($dailyAllowance->status, ['draft'])) {
            abort(403);
        }

        $validated = $request->validate([
            'claim_date' => 'required|date',
            'allowance_type' => 'required|in:full_day,breakfast,lunch,dinner',
            'currency' => 'required|in:MYR,USD',
            'daily_rate' => 'required|numeric|min:0',
            'purpose' => 'required|string|max:500',
            'destination' => 'required|string|max:255',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|mimes:jpg,jpeg,png,pdf|max:10240',
            'save_as_draft' => 'boolean',
        ]);

        // Calculate claim amount based on allowance type
        $claimPercentage = DailyAllowance::getPercentageByType($validated['allowance_type']);
        $claimAmount = ($validated['daily_rate'] * $claimPercentage) / 100;

        // Determine status
        $status = $request->boolean('save_as_draft') ? 'draft' : 'submitted';

        $dailyAllowance->update([
            'claim_date' => $validated['claim_date'],
            'allowance_type' => $validated['allowance_type'],
            'currency' => $validated['currency'],
            'daily_rate' => (float) $validated['daily_rate'], // Ensure float type
            'claim_percentage' => $claimPercentage,
            'claim_amount' => (float) $claimAmount, // Ensure float type
            'purpose' => $validated['purpose'],
            'destination' => $validated['destination'],
            'status' => $status,
        ]);

        // Handle file uploads
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $dailyAllowance->addMedia($file)->toMediaCollection('receipts');
            }
        }

        $message = $status === 'draft'
            ? 'Daily allowance claim saved as draft successfully!'
            : 'Daily allowance claim updated successfully!';

        return redirect()->route('user.daily-allowances.show', $dailyAllowance)
            ->with('success', $message);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DailyAllowance $dailyAllowance)
    {
        // Authorization check - user can only delete their own draft claims
        if ($dailyAllowance->user_id !== Auth::id() || $dailyAllowance->status !== 'draft') {
            abort(403);
        }

        $dailyAllowance->delete();

        return redirect()->route('user.request-claim.index')
            ->with('success', 'Daily allowance claim deleted successfully!');
    }

    /**
     * Submit a draft claim for approval
     */
    public function submit(Request $request, DailyAllowance $dailyAllowance)
    {
        // Authorization check - user can only submit their own draft claims
        if ($dailyAllowance->user_id !== Auth::id() || $dailyAllowance->status !== 'draft') {
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

        $dailyAllowance->update([
            'approver_id' => $approver->id,
            'status' => 'submitted',
        ]);

        // Send notification to approver
        dispatch(new SendClaimSubmittedNotification($dailyAllowance, 'daily', Auth::user()));

        return redirect()->route('user.daily-allowances.show', $dailyAllowance)
            ->with('success', 'Daily allowance claim submitted for approval successfully!');
    }

    public function generate(DailyAllowance $dailyAllowance)
    {
        // Load related data
        $dailyAllowance->load(['user', 'approver']);
        
        $data = [
            'dailyAllowance' => $dailyAllowance,
            'formatCurrency' => function ($amount, $currency = 'MYR') {
                $symbols = [
                    'MYR' => 'RM ',
                    'USD' => '$ '
                ];
                return ($symbols[$currency] ?? '') . number_format($amount, 2);
            },
            'formatDate' => function ($date) {
                return $date ? \Carbon\Carbon::parse($date)->format('d/m/Y') : '—';
            },
            'currentDate' => now()->format('d/m/Y'),
        ];

        $pdf = Pdf::loadView('pdf.daily-allowance', $data);
        
        $pdf->setPaper('A4', 'portrait');
        $pdf->setOptions([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
            'defaultFont' => 'Arial',
        ]);

        return $pdf->stream("daily-allowance-{$dailyAllowance->id}.pdf");
    }
}
