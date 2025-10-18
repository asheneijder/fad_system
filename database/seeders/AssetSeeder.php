<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Asset;
use App\Enums\AssetStatus;

class AssetSeeder extends Seeder
{
    public function run(): void
    {
        $modelTypeId = \App\Models\ModelType::first()?->id ?? 1;
        $categoryTypeId = \App\Models\Category::first()?->id ?? 1;
        $userId = \App\Models\User::first()?->id ?? 1;

        $assets = [
            [
                'asset_name' => 'Dell Latitude 5420',
                'asset_tag_no' => 'DLT-001',
                'serial_no' => 'SN001-5420',
                'model_type_id' => $modelTypeId,
                'category_type_id' => $categoryTypeId,
                'status' => AssetStatus::ASSIGNED->value,
                'qty' => 5,
                'location' => 'HQ Office',
                'purchase_cost' => 3200.00,
                'current_value' => 2400.00,
                'purchase_date' => '2023-05-01',
                'updated_by' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'asset_name' => 'HP LaserJet Pro',
                'asset_tag_no' => 'HPL-002',
                'serial_no' => 'SN002-LJ',
                'model_type_id' => $modelTypeId,
                'category_type_id' => $categoryTypeId,
                'status' => AssetStatus::AVAILABLE->value,
                'qty' => 2,
                'location' => 'Branch Office',
                'purchase_cost' => 1500.00,
                'current_value' => 1000.00,
                'purchase_date' => '2022-08-15',
                'updated_by' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        Asset::insert($assets);
    }
}
