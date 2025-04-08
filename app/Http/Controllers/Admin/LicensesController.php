<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LicensesController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Licenses/Index');
    }
}
