<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\License;
use Carbon\Carbon;

class LicenseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        License::insert([
            [
                'license_name'    => 'Windows 11 Pro',
                'product_key'     => 'XXXXX-XXXXX-XXXXX-XXXXX-WIN11',
                'expiration_date' => Carbon::now()->addYears(2),
                'licensed_email'  => 'admin@company.com',
                'licensed_name'   => 'Company Admin',
                'manufacturer'    => 'Microsoft',
                'min_qty'         => 1,
                'total_qty'       => 10,
                'available_qty'   => 8,
                'status'          => true,
                'updated_by'      => 1,
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            [
                'license_name'    => 'Adobe Creative Cloud',
                'product_key'     => 'XXXXX-XXXXX-XXXXX-XXXXX-ADOBE',
                'expiration_date' => Carbon::now()->addMonths(18),
                'licensed_email'  => 'design@company.com',
                'licensed_name'   => 'Design Dept',
                'manufacturer'    => 'Adobe',
                'min_qty'         => 2,
                'total_qty'       => 5,
                'available_qty'   => 2,
                'status'          => true,
                'updated_by'      => 1,
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
        ]);
    }
}
