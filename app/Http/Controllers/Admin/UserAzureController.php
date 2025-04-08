<?php

namespace App\Http\Controllers\Admin;

use Inertia\Inertia;
use App\Jobs\ImportUsersJob;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class UserAzureController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Settings/Import');
    }
    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:csv,xls,xlsx',
        ]);

        $file = $request->file('file');

        $fileContents = file_get_contents($file->getRealPath());

        ImportUsersJob::dispatch($fileContents);

        return to_route('admin.index.import')->with('success', 'Data imported successfully.');
    }
}
