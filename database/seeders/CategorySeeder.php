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

        // Clear existing categories
        Category::truncate();

        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $this->command->info('Starting asset categories seeding...');

        // Main asset categories
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
                'name' => 'Servers',
                'description' => 'Server hardware and equipment',
                'type' => 'asset',
                'status' => true,
                'parent_id' => null,
                'sort_order' => 2,
            ],
            [
                'name' => 'Networking',
                'description' => 'Network infrastructure equipment',
                'type' => 'asset',
                'status' => true,
                'parent_id' => null,
                'sort_order' => 3,
            ],
            [
                'name' => 'Printers & Scanners',
                'description' => 'Printing and scanning equipment',
                'type' => 'asset',
                'status' => true,
                'parent_id' => null,
                'sort_order' => 4,
            ],

            // Office Equipment
            [
                'name' => 'Audio Visual',
                'description' => 'Projectors, screens and presentation equipment',
                'type' => 'asset',
                'status' => true,
                'parent_id' => null,
                'sort_order' => 5,
            ],
            [
                'name' => 'Telecommunication',
                'description' => 'Phones and communication devices',
                'type' => 'asset',
                'status' => true,
                'parent_id' => null,
                'sort_order' => 6,
            ],
            [
                'name' => 'Office Equipment',
                'description' => 'General office machinery',
                'type' => 'asset',
                'status' => true,
                'parent_id' => null,
                'sort_order' => 7,
            ],

            // Furniture
            [
                'name' => 'Furniture',
                'description' => 'Office furniture and fixtures',
                'type' => 'asset',
                'status' => true,
                'parent_id' => null,
                'sort_order' => 8,
            ],

            // Other Assets
            [
                'name' => 'Vehicles',
                'description' => 'Company vehicles and transportation',
                'type' => 'asset',
                'status' => true,
                'parent_id' => null,
                'sort_order' => 9,
            ],
            [
                'name' => 'Tools & Equipment',
                'description' => 'Maintenance tools and equipment',
                'type' => 'asset',
                'status' => true,
                'parent_id' => null,
                'sort_order' => 10,
            ],
        ];

        // Create main categories and store their IDs
        $mainCategories = [];
        foreach ($categories as $categoryData) {
            $category = Category::create($categoryData);
            $mainCategories[$category->name] = $category->id;
            $this->command->info("Created main category: {$category->name}");
        }

        // Sub-categories for Computers
        $computerSubCategories = [
            [
                'name' => 'Desktop Computers',
                'description' => 'Office desktop workstations',
                'type' => 'asset',
                'status' => true,
                'parent_id' => $mainCategories['Computers'],
                'sort_order' => 1,
            ],
            [
                'name' => 'Laptops',
                'description' => 'Portable laptop computers',
                'type' => 'asset',
                'status' => true,
                'parent_id' => $mainCategories['Computers'],
                'sort_order' => 2,
            ],
            [
                'name' => 'Monitors',
                'description' => 'Computer displays and screens',
                'type' => 'asset',
                'status' => true,
                'parent_id' => $mainCategories['Computers'],
                'sort_order' => 3,
            ],
            [
                'name' => 'Keyboards & Mice',
                'description' => 'Input devices and accessories',
                'type' => 'asset',
                'status' => true,
                'parent_id' => $mainCategories['Computers'],
                'sort_order' => 4,
            ],
            [
                'name' => 'Computer Accessories',
                'description' => 'Other computer peripherals',
                'type' => 'asset',
                'status' => true,
                'parent_id' => $mainCategories['Computers'],
                'sort_order' => 5,
            ],
        ];

        foreach ($computerSubCategories as $subCategory) {
            $category = Category::create($subCategory);
            $this->command->info("Created sub-category: {$category->name}");
        }

        // Sub-categories for Servers
        $serverSubCategories = [
            [
                'name' => 'Rack Servers',
                'description' => 'Rack-mounted server units',
                'type' => 'asset',
                'status' => true,
                'parent_id' => $mainCategories['Servers'],
                'sort_order' => 1,
            ],
            [
                'name' => 'Tower Servers',
                'description' => 'Standalone server towers',
                'type' => 'asset',
                'status' => true,
                'parent_id' => $mainCategories['Servers'],
                'sort_order' => 2,
            ],
            [
                'name' => 'Server Racks',
                'description' => 'Server rack cabinets',
                'type' => 'asset',
                'status' => true,
                'parent_id' => $mainCategories['Servers'],
                'sort_order' => 3,
            ],
            [
                'name' => 'Storage Arrays',
                'description' => 'NAS and SAN storage systems',
                'type' => 'asset',
                'status' => true,
                'parent_id' => $mainCategories['Servers'],
                'sort_order' => 4,
            ],
        ];

        foreach ($serverSubCategories as $subCategory) {
            $category = Category::create($subCategory);
            $this->command->info("Created sub-category: {$category->name}");
        }

        // Sub-categories for Networking
        $networkingSubCategories = [
            [
                'name' => 'Switches',
                'description' => 'Network switches',
                'type' => 'asset',
                'status' => true,
                'parent_id' => $mainCategories['Networking'],
                'sort_order' => 1,
            ],
            [
                'name' => 'Routers',
                'description' => 'Network routers',
                'type' => 'asset',
                'status' => true,
                'parent_id' => $mainCategories['Networking'],
                'sort_order' => 2,
            ],
            [
                'name' => 'Wireless Access Points',
                'description' => 'WiFi access points',
                'type' => 'asset',
                'status' => true,
                'parent_id' => $mainCategories['Networking'],
                'sort_order' => 3,
            ],
            [
                'name' => 'Network Cables',
                'description' => 'Ethernet and fiber cables',
                'type' => 'asset',
                'status' => true,
                'parent_id' => $mainCategories['Networking'],
                'sort_order' => 4,
            ],
            [
                'name' => 'Modems',
                'description' => 'Internet modems',
                'type' => 'asset',
                'status' => true,
                'parent_id' => $mainCategories['Networking'],
                'sort_order' => 5,
            ],
        ];

        foreach ($networkingSubCategories as $subCategory) {
            $category = Category::create($subCategory);
            $this->command->info("Created sub-category: {$category->name}");
        }

        // Sub-categories for Printers & Scanners
        $printerSubCategories = [
            [
                'name' => 'Laser Printers',
                'description' => 'Black and white laser printers',
                'type' => 'asset',
                'status' => true,
                'parent_id' => $mainCategories['Printers & Scanners'],
                'sort_order' => 1,
            ],
            [
                'name' => 'Color Printers',
                'description' => 'Color laser and inkjet printers',
                'type' => 'asset',
                'status' => true,
                'parent_id' => $mainCategories['Printers & Scanners'],
                'sort_order' => 2,
            ],
            [
                'name' => 'Multifunction Devices',
                'description' => 'Print, scan, copy machines',
                'type' => 'asset',
                'status' => true,
                'parent_id' => $mainCategories['Printers & Scanners'],
                'sort_order' => 3,
            ],
            [
                'name' => 'Document Scanners',
                'description' => 'High-speed document scanners',
                'type' => 'asset',
                'status' => true,
                'parent_id' => $mainCategories['Printers & Scanners'],
                'sort_order' => 4,
            ],
        ];

        foreach ($printerSubCategories as $subCategory) {
            $category = Category::create($subCategory);
            $this->command->info("Created sub-category: {$category->name}");
        }

        // Sub-categories for Audio Visual
        $avSubCategories = [
            [
                'name' => 'Projectors',
                'description' => 'Video and data projectors',
                'type' => 'asset',
                'status' => true,
                'parent_id' => $mainCategories['Audio Visual'],
                'sort_order' => 1,
            ],
            [
                'name' => 'Screens & Displays',
                'description' => 'Projection screens and large displays',
                'type' => 'asset',
                'status' => true,
                'parent_id' => $mainCategories['Audio Visual'],
                'sort_order' => 2,
            ],
            [
                'name' => 'Sound Systems',
                'description' => 'Speakers and audio equipment',
                'type' => 'asset',
                'status' => true,
                'parent_id' => $mainCategories['Audio Visual'],
                'sort_order' => 3,
            ],
            [
                'name' => 'Video Conferencing',
                'description' => 'Video conference systems',
                'type' => 'asset',
                'status' => true,
                'parent_id' => $mainCategories['Audio Visual'],
                'sort_order' => 4,
            ],
        ];

        foreach ($avSubCategories as $subCategory) {
            $category = Category::create($subCategory);
            $this->command->info("Created sub-category: {$category->name}");
        }

        // Sub-categories for Telecommunication
        $telecomSubCategories = [
            [
                'name' => 'Desk Phones',
                'description' => 'Office desk telephones',
                'type' => 'asset',
                'status' => true,
                'parent_id' => $mainCategories['Telecommunication'],
                'sort_order' => 1,
            ],
            [
                'name' => 'Mobile Phones',
                'description' => 'Company mobile phones',
                'type' => 'asset',
                'status' => true,
                'parent_id' => $mainCategories['Telecommunication'],
                'sort_order' => 2,
            ],
            [
                'name' => 'PBX Systems',
                'description' => 'Phone system equipment',
                'type' => 'asset',
                'status' => true,
                'parent_id' => $mainCategories['Telecommunication'],
                'sort_order' => 3,
            ],
            [
                'name' => 'Headsets',
                'description' => 'Telephone headsets',
                'type' => 'asset',
                'status' => true,
                'parent_id' => $mainCategories['Telecommunication'],
                'sort_order' => 4,
            ],
        ];

        foreach ($telecomSubCategories as $subCategory) {
            $category = Category::create($subCategory);
            $this->command->info("Created sub-category: {$category->name}");
        }

        // Sub-categories for Furniture
        $furnitureSubCategories = [
            [
                'name' => 'Office Chairs',
                'description' => 'Executive and task chairs',
                'type' => 'asset',
                'status' => true,
                'parent_id' => $mainCategories['Furniture'],
                'sort_order' => 1,
            ],
            [
                'name' => 'Desks',
                'description' => 'Office desks and workstations',
                'type' => 'asset',
                'status' => true,
                'parent_id' => $mainCategories['Furniture'],
                'sort_order' => 2,
            ],
            [
                'name' => 'Filing Cabinets',
                'description' => 'Document storage cabinets',
                'type' => 'asset',
                'status' => true,
                'parent_id' => $mainCategories['Furniture'],
                'sort_order' => 3,
            ],
            [
                'name' => 'Meeting Tables',
                'description' => 'Conference and meeting tables',
                'type' => 'asset',
                'status' => true,
                'parent_id' => $mainCategories['Furniture'],
                'sort_order' => 4,
            ],
            [
                'name' => 'Bookshelves',
                'description' => 'Storage shelves and bookcases',
                'type' => 'asset',
                'status' => true,
                'parent_id' => $mainCategories['Furniture'],
                'sort_order' => 5,
            ],
        ];

        foreach ($furnitureSubCategories as $subCategory) {
            $category = Category::create($subCategory);
            $this->command->info("Created sub-category: {$category->name}");
        }

        $totalCategories = Category::count();
        $this->command->info('Asset categories seeding completed successfully!');
        $this->command->info("Total asset categories created: {$totalCategories}");

        // Display category counts
        $this->command->info('Category breakdown:');
        $this->command->info(' - Main categories: '.Category::whereNull('parent_id')->count());
        $this->command->info(' - Sub-categories: '.Category::whereNotNull('parent_id')->count());
    }
}
