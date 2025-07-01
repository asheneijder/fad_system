<?php

namespace App\Http\Controllers\Admin;

use Inertia\Inertia;
use App\Models\CategoryType;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class CategoryController extends Controller
{
    public function __construct(protected CategoryType $categoryType) {}
    /**
     * Display a listing of the resource.
     */
    public function index(Request $req)
    {
        $search = $req->query('search');

        $categories = $this->categoryType->query()
            ->when($search, fn($q) =>
            $q->where('category_name', 'like', "%{$search}%"))
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Admin/Category/Index', [
            'categories' => $categories,
            'filters' => $req->only(['search']) + ['page' => $categories->currentPage()],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
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
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
