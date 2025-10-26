<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Models\AssetAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AssetAssignmentController extends Controller
{
    /**
     * Show confirmation page
     */
    public function showConfirmation(Request $request, AssetAssignment $assignment)
    {
        // Middleware validates signature automatically

        // Check if already acknowledged
        if ($assignment->acknowledged_at) {
            return view('asset-acknowledged', [
                'assignment' => $assignment->load(['asset', 'user']),
            ]);
        }

        // Load the correct relationships from your model
        $assignment->load(['asset', 'assignedBy', 'user']);

        return view('asset-confirmation', [
            'assignment' => $assignment,
        ]);
    }

    /**
     * Process the acknowledgment
     */
    public function acknowledge(Request $request, AssetAssignment $assignment)
    {
        // Check if already acknowledged
        if ($assignment->acknowledged_at) {
            return view('asset-acknowledged', [
                'assignment' => $assignment->load(['asset', 'user']),
            ]);
        }

        try {
            DB::beginTransaction();

            // Update acknowledged_at
            $assignment->update([
                'acknowledged_at' => now(),
            ]);

            // Log activity
            activity()
                ->causedBy($assignment->assignedBy)
                ->performedOn($assignment->asset)
                ->withProperties([
                    'acknowledged_via' => 'email_link',
                    'acknowledged_by_user' => $assignment->user->name,
                    'acknowledged_at' => now()->toDateTimeString(),
                ])
                ->log('asset assignment acknowledged by user via email');

            DB::commit();

            return view('asset-acknowledged', [
                'assignment' => $assignment->load(['asset', 'user']),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Failed to acknowledge assignment: '.$e->getMessage());
        }
    }
}
