<?php

namespace Database\Seeders;

use App\Models\StationaryItem;
use App\Models\StationaryItemMovement;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class StationaryItemMovementSeeder extends Seeder
{
    public function run(): void
    {
        $paper = StationaryItem::where('description', 'A4 Paper 80gsm')->first();
        $pen = StationaryItem::where('description', 'Ballpoint Pen Blue')->first();

        // A4 Paper - June 2025 stock movement
        StationaryItemMovement::create([
            'stationary_item_id' => $paper->id,
            'movement_date' => Carbon::parse('2025-06-01'),
            'brought_forward' => 100,
            'in' => 50,
            'out' => 30,
            'closing_balance' => 120,
        ]);

        // Ballpoint Pen - June 2025 stock movement
        StationaryItemMovement::create([
            'stationary_item_id' => $pen->id,
            'movement_date' => Carbon::parse('2025-06-01'),
            'brought_forward' => 200,
            'in' => 100,
            'out' => 60,
            'closing_balance' => 240,
        ]);
    }
}
