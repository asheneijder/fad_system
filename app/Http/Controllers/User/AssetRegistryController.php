<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\AssetAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AssetRegistryController extends Controller
{
    public function index()
    {
        $userAssignments = AssetAssignment::with(['asset', 'asset.model', 'asset.category'])
            ->where('assigned_to', Auth::id())
            ->orderBy('assigned_at', 'desc')
            ->get();

        return Inertia::render('User/AssetRegistry/Index', [
            'userAssignments' => $userAssignments
        ]);
    }

    public function acknowledge(Request $request, AssetAssignment $assignment)
    {
        // Verify the assignment belongs to the current user
        if ($assignment->assigned_to !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        // Check if already acknowledged
        if ($assignment->acknowledged_at) {
            return redirect()->back()->with('error', 'Assignment already acknowledged.');
        }

        $assignment->markAcknowledged();

        return redirect()->back()->with('success', 'Asset assignment acknowledged successfully.');
    }
}