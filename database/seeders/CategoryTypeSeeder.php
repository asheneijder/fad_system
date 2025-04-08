<?php

namespace Database\Seeders;

use App\Models\CategoryType;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class CategoryTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['category_name' => 'Electronics'],
            ['category_name' => 'Furniture'],
            ['category_name' => 'Vehicles'],
        ];

        foreach ($categories as $category) {
            CategoryType::create($category);
        }
    }
}
