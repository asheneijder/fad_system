<?php

namespace Database\Seeders;

use App\Models\ModelType;
use App\Models\CategoryType;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class ModelTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $electronics = CategoryType::where('category_name', 'Electronics')->first();
        $furniture = CategoryType::where('category_name', 'Furniture')->first();
        $vehicles = CategoryType::where('category_name', 'Vehicles')->first();

        $models = [
            ['category_type_id' => $electronics->id, 'model_name' => 'Laptop', 'model_no' => 'LAP123', 'description' => 'High-end gaming laptop'],
            ['category_type_id' => $electronics->id, 'model_name' => 'Smartphone', 'model_no' => 'SM456', 'description' => 'Latest smartphone'],
            ['category_type_id' => $furniture->id, 'model_name' => 'Office Chair', 'model_no' => 'CHAIR789', 'description' => 'Ergonomic office chair'],
            ['category_type_id' => $vehicles->id, 'model_name' => 'Sedan', 'model_no' => 'CAR001', 'description' => 'Luxury sedan'],
        ];

        foreach ($models as $model) {
            ModelType::create($model);
        }
    }
}
