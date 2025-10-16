<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ManageUserController extends Controller
{
    public function __construct(protected User $user) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->query('search');
        $status = $request->query('status');

        $users = $this->user->query()
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('job_title', 'like', "%{$search}%")
                        ->orWhere('department', 'like', "%{$search}%")
                        ->orWhere('office_location', 'like', "%{$search}%");
                });
            })
            ->when($status !== null && $status !== '', function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        // Statistics - Since we don't have status field yet, we'll use basic counts
        $statistics = [
            'total' => $this->user->count(),
            'active' => $this->user->count(), // Default all users as active for now
            'this_month' => $this->user->whereYear('created_at', now()->year)
                ->whereMonth('created_at', now()->month)
                ->count(),
            'with_job_title' => $this->user->whereNotNull('job_title')->count(),
        ];

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'filters' => $request->only(['search', 'status']),
            'statistics' => $statistics,
        ]);
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

        try {
            DB::beginTransaction();

            $user = $this->user->create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'job_title' => $validated['job_title'],
                'department' => $validated['department'],
                'office_location' => $validated['office_location'],
                'password' => Hash::make($validated['password']),
                'email_verified_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'User created successfully',
                'user' => $user,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to create user: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        return Inertia::render('Admin/Users/Show', [
            'user' => $user->load('assets'),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
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

        try {
            DB::beginTransaction();

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

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'User updated successfully',
                'user' => $user->fresh(),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to update user: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        // Prevent deletion of current user
        if ($user->id === Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot delete your own account.',
            ], 422);
        }

        try {
            DB::beginTransaction();

            $user->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete user: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reset user password to default
     */
    public function resetPassword(User $user)
    {
        // Prevent resetting own password via this method
        if ($user->id === Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot reset your own password using this method.',
            ], 422);
        }

        try {
            DB::beginTransaction();

            $defaultPassword = 'st@ff!@Rt!';
            $user->update([
                'password' => Hash::make($defaultPassword),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Password reset to default successfully',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to reset password: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Bulk reset passwords
     */
    public function bulkResetPassword(Request $request)
    {
        $validated = $request->validate([
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'exists:users,id',
        ]);

        $userIds = $validated['user_ids'];

        // Remove current user from reset list
        $userIds = array_filter($userIds, fn ($id) => $id != Auth::id());

        if (empty($userIds)) {
            return response()->json([
                'success' => false,
                'message' => 'No valid users selected for password reset.',
            ], 422);
        }

        try {
            DB::beginTransaction();

            $defaultPassword = 'st@ff!@Rt!';
            $this->user->whereIn('id', $userIds)->update([
                'password' => Hash::make($defaultPassword),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => count($userIds).' user password(s) reset successfully',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to reset passwords: '.$e->getMessage(),
            ], 500);
        }
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

        try {
            DB::beginTransaction();

            $deletedCount = $this->user->whereIn('id', $userIds)->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $deletedCount.' user(s) deleted successfully',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete users: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Export users data
     */
    public function export(Request $request)
    {
        $userIds = $request->input('user_ids', []);

        $users = $this->user->query()
            ->when(! empty($userIds), function ($query) use ($userIds) {
                $query->whereIn('id', $userIds);
            })
            ->select('name', 'email', 'job_title', 'department', 'office_location', 'created_at')
            ->get()
            ->map(function ($user) {
                return [
                    'Name' => $user->name,
                    'Email' => $user->email,
                    'Job Title' => $user->job_title ?? 'N/A',
                    'Department' => $user->department ?? 'N/A',
                    'Office Location' => $user->office_location ?? 'N/A',
                    'Joined Date' => $user->created_at->format('Y-m-d H:i:s'),
                ];
            });

        $filename = 'users_'.now()->format('Y-m-d').'.csv';

        // For CSV export
        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $users,
                'filename' => $filename,
            ]);
        }

        // For direct download
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];

        $callback = function () use ($users) {
            $file = fopen('php://output', 'w');

            // Add headers
            fputcsv($file, array_keys($users->first() ?? []));

            // Add data
            foreach ($users as $user) {
                fputcsv($file, $user);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Search users (for autocomplete or API usage)
     */
    public function search(Request $request)
    {
        $query = $request->get('q', '');
        $limit = min($request->get('limit', 10), 50);

        $users = $this->user->query()
            ->when($query, function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%");
            })
            ->select('id', 'name', 'email', 'job_title')
            ->limit($limit)
            ->get();

        return response()->json($users);
    }
}
