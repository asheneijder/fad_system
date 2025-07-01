<?php

namespace App\Http\Controllers\Admin;

use Inertia\Inertia;
use App\Models\ModelType;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ModelController extends Controller
{
    public function __construct(protected ModelType $model) {}
    /**
     * Display a listing of the resource.
     */
    public function index(Request $req)
    {
        $search = $req->query('search');

        $models = $this->model->query()
            ->with(['categoryType'])
            ->when(
                $search,
                fn($query) =>
                $query->where('model_name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
            )
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Admin/Model/Index', [
            'models' => $models,
            'filters' => $req->only(['search']) + ['page' => $models->currentPage()],
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
