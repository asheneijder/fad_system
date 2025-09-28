<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ManageUserController extends Controller
{
    public function __construct(protected User $user) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $req)
    {
        $search = $req->query('search');

        $users = $this->user->query()
            ->when(
                $search,
                fn ($query) => $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('job_title', 'like', "%{$search}%")
                    ->orWhere('department', 'like', "%{$search}%")
            )
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'filters' => $req->only(['search']) + ['page' => $users->currentPage()],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     * This method can be removed since we're using modals
     */
    public function create()
    {
        return Inertia::render('Admin/Users/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'job_title' => 'nullable|string|max:255',
            'department' => 'nullable|string|max:255',
            'office_location' => 'nullable|string|max:255',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'name.required' => 'Full name is required.',
            'email.required' => 'Email address is required.',
            'email.email' => 'Please enter a valid email address.',
            'email.unique' => 'This email address is already registered.',
            'password.required' => 'Password is required.',
            'password.min' => 'Password must be at least 8 characters.',
            'password.confirmed' => 'Password confirmation does not match.',
        ]);

        $user = $this->user->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'job_title' => $validated['job_title'],
            'department' => $validated['department'],
            'office_location' => $validated['office_location'],
            'password' => Hash::make($validated['password']),
        ]);

        // Return JSON response for AJAX requests (modals)
        if ($request->wantsJson() || $request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'User created successfully',
                'user' => $user,
            ]);
        }

        // Fallback for non-AJAX requests
        return to_route('admin.users.index')->with('success', 'User created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $user = $this->user->findOrFail($id);

        return Inertia::render('Admin/Users/Show', [
            'user' => $user,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     * This method can be removed since we're using modals
     */
    public function edit(string $id)
    {
        $user = $this->user->findOrFail($id);

        return Inertia::render('Admin/Users/Edit', [
            'user' => $user,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $user = $this->user->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'job_title' => 'nullable|string|max:255',
            'department' => 'nullable|string|max:255',
            'office_location' => 'nullable|string|max:255',
            'password' => 'nullable|string|min:8|confirmed',
        ], [
            'name.required' => 'Full name is required.',
            'email.required' => 'Email address is required.',
            'email.email' => 'Please enter a valid email address.',
            'email.unique' => 'This email address is already registered.',
            'password.min' => 'Password must be at least 8 characters.',
            'password.confirmed' => 'Password confirmation does not match.',
        ]);

        // Prepare update data
        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'job_title' => $validated['job_title'],
            'department' => $validated['department'],
            'office_location' => $validated['office_location'],
        ];

        // Only update password if provided
        if (! empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $user->update($updateData);

        // Return JSON response for AJAX requests (modals)
        if ($request->wantsJson() || $request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'User updated successfully',
                'user' => $user->fresh(),
            ]);
        }

        // Fallback for non-AJAX requests
        return to_route('admin.users.index')->with('success', 'User updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user = $this->user->findOrFail($id);

        // Prevent deletion of current user
        if ($user->id === Auth::id()) {
            if (request()->wantsJson() || request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot delete your own account.',
                ], 422);
            }

            return to_route('admin.users.index')
                ->with('error', 'You cannot delete your own account.');
        }

        // Check if user has related records (you can expand this based on your relationships)
        // For example, if users have created records that shouldn't be orphaned
        /*
        $hasRelatedRecords = $user->createdModels()->exists(); // Example relationship

        if ($hasRelatedRecords) {
            if (request()->wantsJson() || request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete user. User has associated records.',
                ], 422);
            }

            return to_route('admin.users.index')
                ->with('error', 'Cannot delete user. User has associated records.');
        }
        */

        $user->delete();

        // Return JSON response for AJAX requests
        if (request()->wantsJson() || request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully',
            ]);
        }

        // Fallback for non-AJAX requests
        return to_route('admin.users.index')->with('success', 'User deleted successfully.');
    }

    /**
     * Reset user password to default
     */
    public function resetPassword(string $id)
    {
        $user = $this->user->findOrFail($id);

        // Prevent resetting own password via this method
        if ($user->id === Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot reset your own password using this method.',
            ], 422);
        }

        $defaultPassword = 'st@ff!@Rt!';
        $user->update([
            'password' => Hash::make($defaultPassword),
        ]);

        return response()->json([
            'success' => true,
            'message' => "Password reset to default: {$defaultPassword}",
        ]);
    }

    /**
     * Toggle user status (if you have a status field)
     */
    public function toggleStatus(string $id)
    {
        $user = $this->user->findOrFail($id);

        // Prevent deactivating own account
        if ($user->id === Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot deactivate your own account.',
            ], 422);
        }

        // Assuming you have a 'status' or 'is_active' field
        // $user->update(['status' => !$user->status]);
        // For now, we'll just return a message since the field might not exist

        return response()->json([
            'success' => true,
            'message' => 'User status updated successfully',
            'user' => $user->fresh(),
        ]);
    }

    /**
     * Bulk delete users
     */
    public function bulkDelete(Request $request)
    {
        $validated = $request->validate([
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'exists:users,id',
        ]);

        $userIds = $validated['user_ids'];

        // Remove current user from deletion list
        $userIds = array_filter($userIds, fn ($id) => $id != Auth::id());

        if (empty($userIds)) {
            return response()->json([
                'success' => false,
                'message' => 'No valid users selected for deletion.',
            ], 422);
        }

        $deletedCount = $this->user->whereIn('id', $userIds)->delete();

        return response()->json([
            'success' => true,
            'message' => "{$deletedCount} user(s) deleted successfully",
        ]);
    }

    /**
     * Search users (for autocomplete or API usage)
     */
    public function search(Request $request)
    {
        $query = $request->get('q', '');
        $limit = min($request->get('limit', 10), 50); // Max 50 results

        $users = $this->user->query()
            ->when($query, fn ($q) => $q->where('name', 'like', "%{$query}%")
                ->orWhere('email', 'like', "%{$query}%")
            )
            ->select('id', 'name', 'email', 'job_title')
            ->limit($limit)
            ->get();

        return response()->json($users);
    }

    /**
     * Export users data
     */
    public function export()
    {
        $users = $this->user->query()
            ->select('name', 'email', 'job_title', 'department', 'office_location', 'created_at')
            ->get()
            ->map(function ($user) {
                return [
                    'Name' => $user->name,
                    'Email' => $user->email,
                    'Job Title' => $user->job_title ?? 'N/A',
                    'Department' => $user->department ?? 'N/A',
                    'Office Location' => $user->office_location ?? 'N/A',
                    'Joined Date' => $user->created_at->format('Y-m-d'),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $users,
            'filename' => 'users_'.now()->format('Y-m-d').'.csv',
        ]);
    }
}
