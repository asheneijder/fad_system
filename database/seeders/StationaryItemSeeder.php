<?php

namespace Database\Seeders;

use App\Models\StationaryItem;
use App\Models\CategoryType;
use Illuminate\Database\Seeder;

class StationaryItemSeeder extends Seeder
{
    public function run(): void
    {
        // Sample categories
        $category = CategoryType::firstOrCreate(['category_name' => 'Office Supplies']);

        // Sample items
        StationaryItem::create([
            'description' => 'A4 Paper 80gsm',
            'category_type_id' => $category->id,
            'unit' => 'Ream',
            'unit_cost' => 12.50,
            'status' => true,
        ]);

        StationaryItem::create([
            'description' => 'Ballpoint Pen Blue',
            'category_type_id' => $category->id,
            'unit' => 'Box',
            'unit_cost' => 5.75,
            'status' => true,
        ]);
    }
}
