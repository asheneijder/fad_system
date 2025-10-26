<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Disable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        Category::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $this->command->info('Starting asset categories seeding...');

        $categories = [
            // IT Equipment
            [
                'name' => 'Computers',
                'description' => 'Desktop computers, laptops and workstations',
                'type' => 'asset',
                'status' => true,
                'parent_id' => null,
                'sort_order' => 1,
            ],
            [
                'name' => 'Air Conditioners',
                'description' => 'Air conditioning units and systems',
                'type' => 'asset',
                'status' => true,
                'parent_id' => null,
                'sort_order' => 2,
            ],
            [
                'name' => 'Office Equipment',
                'description' => 'General office equipment and furniture',
                'type' => 'asset',
                'status' => true,
                'parent_id' => null,
                'sort_order' => 3,
            ],
            [
                'name' => 'Networking',
                'description' => 'Network equipment and infrastructure',
                'type' => 'asset',
                'status' => true,
                'parent_id' => null,
                'sort_order' => 4,
            ],
            [
                'name' => 'Printers & Scanners',
                'description' => 'Printing and scanning equipment',
                'type' => 'asset',
                'status' => true,
                'parent_id' => null,
                'sort_order' => 5,
            ],
            [
                'name' => 'Servers',
                'description' => 'Server equipment and infrastructure',
                'type' => 'asset',
                'status' => true,
                'parent_id' => null,
                'sort_order' => 6,
            ],
            [
                'name' => 'Audio Visual',
                'description' => 'Audio visual equipment and systems',
                'type' => 'asset',
                'status' => true,
                'parent_id' => null,
                'sort_order' => 7,
            ],
            [
                'name' => 'Others',
                'description' => 'Other EDP equipment and miscellaneous items',
                'type' => 'asset',
                'status' => true,
                'parent_id' => null,
                'sort_order' => 8,
            ],
            [
                'name' => 'Furniture & Fittings',
                'description' => 'Office furniture, tables, chairs and fittings',
                'type' => 'asset',
                'status' => true,
                'parent_id' => null,
                'sort_order' => 9,
            ],
            [
                'name' => 'Renovation',
                'description' => 'Office renovation and improvement works',
                'type' => 'asset',
                'status' => true,
                'parent_id' => null,
                'sort_order' => 10,
            ],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }

        $this->command->info('Successfully seeded '.count($categories).' asset categories.');
    }
}
