<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Asset;
use App\Models\User;
use App\Models\AssetAssignment;
use Illuminate\Support\Carbon;

class AssetAssignmentSeeder extends Seeder
{
    public function run(): void
    {
        $assets = Asset::take(3)->get(); // You can adjust the number
        $users = User::take(5)->get();   // Take some users

        foreach ($assets as $index => $asset) {
            $assignedTo = $users[$index % $users->count()];

            AssetAssignment::create([
                'asset_id' => $asset->id,
                'assigned_to' => $assignedTo->id,
                'assigned_at' => Carbon::now()->subDays(rand(30, 100)),
                'returned_at' => rand(0, 1) ? Carbon::now()->subDays(rand(1, 29)) : null,
                'remarks' => rand(0, 1) ? 'Assigned for remote work' : 'Assigned to department',
                'assigned_by' => $users->first()->id, // Admin or any static user
            ]);
        }
    }
}
