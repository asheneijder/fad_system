<?php

namespace App\Jobs;

use App\Imports\UserDetailsImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class ImportUsersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $fileContents;

    public function __construct($fileContents)
    {
        $this->fileContents = $fileContents;
    }

    public function handle()
    {
        // Generate a temporary file
        $tempFilePath = storage_path('app/temp_' . Str::random(10) . '.csv');

        // Save contents to temp file
        file_put_contents($tempFilePath, $this->fileContents);

        // Import using Laravel Excel
        Excel::import(new UserDetailsImport, $tempFilePath);


        // Delete temp file after processing
        unlink($tempFilePath);
    }
}
