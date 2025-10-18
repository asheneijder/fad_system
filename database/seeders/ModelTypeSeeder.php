<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\ModelType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ModelTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Disable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        // Clear existing models
        ModelType::truncate();

        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $this->command->info('Starting model type seeding...');

        // Get category IDs
        $categories = Category::all()->keyBy('name');

        $models = [
            // Computer Models
            [
                'name' => 'Dell Latitude 5420',
                'description' => 'Business laptop with 14" display, Intel Core i5, 8GB RAM, 256GB SSD',
                'category_id' => $categories['Laptops']->id,
                'brand' => 'Dell',
                'model_number' => 'Latitude 5420',
                'specifications' => [
                    'processor' => 'Intel Core i5-1135G7',
                    'ram' => '8GB DDR4',
                    'storage' => '256GB SSD',
                    'display' => '14" FHD',
                    'graphics' => 'Intel Iris Xe',
                ],
                'warranty_period' => 36,
                'status' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'HP EliteDesk 800 G5',
                'description' => 'Desktop computer for office use, compact form factor',
                'category_id' => $categories['Desktop Computers']->id,
                'brand' => 'HP',
                'model_number' => 'EliteDesk 800 G5',
                'specifications' => [
                    'processor' => 'Intel Core i5-10500',
                    'ram' => '8GB DDR4',
                    'storage' => '512GB SSD',
                    'graphics' => 'Intel UHD Graphics 630',
                ],
                'warranty_period' => 36,
                'status' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Dell OptiPlex 3080',
                'description' => 'Small form factor desktop for general office use',
                'category_id' => $categories['Desktop Computers']->id,
                'brand' => 'Dell',
                'model_number' => 'OptiPlex 3080',
                'specifications' => [
                    'processor' => 'Intel Core i3-10100',
                    'ram' => '4GB DDR4',
                    'storage' => '1TB HDD',
                    'graphics' => 'Intel UHD Graphics 630',
                ],
                'warranty_period' => 36,
                'status' => true,
                'sort_order' => 3,
            ],

            // Monitor Models
            [
                'name' => 'Dell P2422H',
                'description' => '24-inch Full HD monitor with IPS panel',
                'category_id' => $categories['Monitors']->id,
                'brand' => 'Dell',
                'model_number' => 'P2422H',
                'specifications' => [
                    'screen_size' => '24"',
                    'resolution' => '1920x1080',
                    'panel_type' => 'IPS',
                    'refresh_rate' => '60Hz',
                    'ports' => 'HDMI, DisplayPort, VGA',
                ],
                'warranty_period' => 36,
                'status' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'HP E27q G4',
                'description' => '27-inch QHD business monitor',
                'category_id' => $categories['Monitors']->id,
                'brand' => 'HP',
                'model_number' => 'E27q G4',
                'specifications' => [
                    'screen_size' => '27"',
                    'resolution' => '2560x1440',
                    'panel_type' => 'IPS',
                    'refresh_rate' => '75Hz',
                    'ports' => 'HDMI, DisplayPort, USB-C',
                ],
                'warranty_period' => 36,
                'status' => true,
                'sort_order' => 2,
            ],

            // Printer Models
            [
                'name' => 'HP LaserJet Pro M404dn',
                'description' => 'Monochrome laser printer for high-volume printing',
                'category_id' => $categories['Laser Printers']->id,
                'brand' => 'HP',
                'model_number' => 'LaserJet Pro M404dn',
                'specifications' => [
                    'type' => 'Monochrome Laser',
                    'print_speed' => '40 ppm',
                    'resolution' => '600x600 dpi',
                    'paper_capacity' => '350 sheets',
                    'connectivity' => 'Ethernet, USB',
                ],
                'warranty_period' => 12,
                'status' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Canon imageCLASS MF644Cdw',
                'description' => 'Color laser multifunction printer',
                'category_id' => $categories['Color Printers']->id,
                'brand' => 'Canon',
                'model_number' => 'MF644Cdw',
                'specifications' => [
                    'type' => 'Color Laser',
                    'functions' => 'Print, Copy, Scan, Fax',
                    'print_speed' => '22 ppm',
                    'connectivity' => 'Wi-Fi, Ethernet, USB',
                ],
                'warranty_period' => 12,
                'status' => true,
                'sort_order' => 1,
            ],

            // Projector Models
            [
                'name' => 'Epson EB-E01',
                'description' => '3LCD projector with 3300 lumens brightness',
                'category_id' => $categories['Projectors']->id,
                'brand' => 'Epson',
                'model_number' => 'EB-E01',
                'specifications' => [
                    'technology' => '3LCD',
                    'brightness' => '3300 lumens',
                    'resolution' => 'XGA (1024x768)',
                    'contrast_ratio' => '15000:1',
                    'lamp_life' => '12000 hours',
                ],
                'warranty_period' => 24,
                'status' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'BenQ MH535',
                'description' => 'Full HD projector for meeting rooms',
                'category_id' => $categories['Projectors']->id,
                'brand' => 'BenQ',
                'model_number' => 'MH535',
                'specifications' => [
                    'technology' => 'DLP',
                    'brightness' => '3600 lumens',
                    'resolution' => '1920x1080',
                    'contrast_ratio' => '15000:1',
                    'throw_distance' => '1.5-10 meters',
                ],
                'warranty_period' => 36,
                'status' => true,
                'sort_order' => 2,
            ],

            // Network Equipment
            [
                'name' => 'Cisco Catalyst 2960-L',
                'description' => '24-port gigabit Ethernet switch',
                'category_id' => $categories['Switches']->id,
                'brand' => 'Cisco',
                'model_number' => 'WS-C2960L-24TS-LL',
                'specifications' => [
                    'ports' => '24 x Gigabit Ethernet',
                    'poe' => 'No',
                    'management' => 'Smart',
                    'stacking' => 'Yes',
                ],
                'warranty_period' => 60,
                'status' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Ubiquiti UniFi AP AC Pro',
                'description' => 'Enterprise WiFi access point',
                'category_id' => $categories['Wireless Access Points']->id,
                'brand' => 'Ubiquiti',
                'model_number' => 'UAP-AC-PRO',
                'specifications' => [
                    'wifi_standard' => '802.11ac',
                    'speed' => '1300 Mbps',
                    'poe' => '802.3af',
                    'mounting' => 'Ceiling/Wall',
                ],
                'warranty_period' => 24,
                'status' => true,
                'sort_order' => 1,
            ],

            // Phone Models
            [
                'name' => 'Cisco IP Phone 7841',
                'description' => 'Basic IP phone for office use',
                'category_id' => $categories['Desk Phones']->id,
                'brand' => 'Cisco',
                'model_number' => 'CP-7841-3PCC-K9',
                'specifications' => [
                    'lines' => '2',
                    'display' => '3.5" grayscale',
                    'poe' => 'Yes',
                    'protocol' => 'SIP',
                ],
                'warranty_period' => 12,
                'status' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Yealink T46S',
                'description' => 'Advanced IP phone with color display',
                'category_id' => $categories['Desk Phones']->id,
                'brand' => 'Yealink',
                'model_number' => 'T46S',
                'specifications' => [
                    'lines' => '12',
                    'display' => '4.3" color',
                    'poe' => 'Yes',
                    'bluetooth' => 'Yes',
                ],
                'warranty_period' => 24,
                'status' => true,
                'sort_order' => 2,
            ],

            // Server Models
            [
                'name' => 'Dell PowerEdge R740',
                'description' => '2U rack server for enterprise applications',
                'category_id' => $categories['Rack Servers']->id,
                'brand' => 'Dell',
                'model_number' => 'PowerEdge R740',
                'specifications' => [
                    'form_factor' => '2U Rack',
                    'processors' => '2 x Intel Xeon',
                    'memory' => '64GB DDR4',
                    'storage' => '8 x 2.5" bays',
                    'power_supply' => 'Dual 750W',
                ],
                'warranty_period' => 60,
                'status' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'HP ProLiant DL380 Gen10',
                'description' => 'Enterprise rack server',
                'category_id' => $categories['Rack Servers']->id,
                'brand' => 'HP',
                'model_number' => 'DL380 Gen10',
                'specifications' => [
                    'form_factor' => '2U Rack',
                    'processors' => '2 x Intel Xeon Silver',
                    'memory' => '32GB DDR4',
                    'storage' => '8 x SFF bays',
                ],
                'warranty_period' => 36,
                'status' => true,
                'sort_order' => 2,
            ],

            // Office Chairs
            [
                'name' => 'Herman Miller Aeron',
                'description' => 'Ergonomic office chair, size B',
                'category_id' => $categories['Office Chairs']->id,
                'brand' => 'Herman Miller',
                'model_number' => 'Aeron Size B',
                'specifications' => [
                    'material' => 'Mesh',
                    'adjustments' => 'Height, Tilt, Lumbar',
                    'weight_capacity' => '159 kg',
                    'warranty' => '12 years',
                ],
                'warranty_period' => 144, // 12 years in months
                'status' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Steelcase Leap V2',
                'description' => 'High-performance office chair',
                'category_id' => $categories['Office Chairs']->id,
                'brand' => 'Steelcase',
                'model_number' => 'Leap V2',
                'specifications' => [
                    'material' => 'Fabric',
                    'adjustments' => 'Full ergonomic',
                    'weight_capacity' => '136 kg',
                    'warranty' => '12 years',
                ],
                'warranty_period' => 144,
                'status' => true,
                'sort_order' => 2,
            ],

            // Some inactive models for testing
            [
                'name' => 'Old CRT Monitor',
                'description' => 'Legacy CRT monitor - no longer in use',
                'category_id' => $categories['Monitors']->id,
                'brand' => 'Generic',
                'model_number' => 'CRT-17',
                'specifications' => [
                    'screen_size' => '17"',
                    'resolution' => '1280x1024',
                    'type' => 'CRT',
                ],
                'warranty_period' => 0,
                'status' => false,
                'sort_order' => 99,
            ],
        ];

        $createdCount = 0;
        foreach ($models as $modelData) {
            ModelType::create($modelData);
            $createdCount++;
            $this->command->info("Created model: {$modelData['name']}");
        }

        $totalModels = ModelType::count();
        $this->command->info('Model seeding completed successfully!');
        $this->command->info("Total models created: {$totalModels}");

        // Display model counts by category
        $categoryCounts = ModelType::with('category')
            ->selectRaw('category_id, count(*) as count')
            ->groupBy('category_id')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->category->name => $item->count];
            })
            ->toArray();

        $this->command->info('Model breakdown by category:');
        foreach ($categoryCounts as $category => $count) {
            $this->command->info(" - {$category}: {$count} models");
        }
    }
}
