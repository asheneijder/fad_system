<?php

namespace App\Http\Controllers\Admin;

use Inertia\Inertia;
use App\Models\License;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class LicensesController extends Controller
{
    public function index(Request $req)
    {
        $search = $req->query('search');

        $licenses = License::when(
            $search,
            fn($q) =>
            $q->where('license_name', 'like', "%{$search}%")
                ->orWhere('product_key', 'like', "%{$search}%")
        )
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Admin/Licenses/Index', [
            'licenses' => $licenses,
            'filters' => $req->only(['search']) + ['page' => $licenses->currentPage()],
        ]);
    }

    public function show(License $license)
    {
        $license->load([]);
        
        return Inertia::render('Admin/Licenses/Show', [
            'license' => $license,
        ]);
    }
}
