<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\StoreUserRequest;

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
                fn($query) =>
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
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
     */
    public function create()
    {
        return Inertia::render('Admin/Users/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserRequest $req)
    {
        $this->user->create(array_merge(
            $req->validated(),
            [
                'password' => Hash::make('st@ff!@Rt!'),
            ]
        ));

        return to_route('admin.users.index')->with('success', 'User created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $user = $this->user->findOrFail($id);

        return Inertia::render('Admin/Users/Edit', [
            'user' => $user
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StoreUserRequest $req, string $id)
    {
        $user = $this->user->findOrFail($id);

        $user->update($req->validated());

        return to_route('admin.users.index')->with('success', 'User updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user = $this->user->findOrFail($id);

        $user->delete();

        return to_route('admin.users.index')->with('success', 'User deleted successfully.');
    }
}
