<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class RequestItemController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/RequestItem/Index');
    }

    public function create()
    {
        return Inertia::render('Admin/RequestItem/Create');
    }

    public function store(Request $request)
    {
        //
    }
}
