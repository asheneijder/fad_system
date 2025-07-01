<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Database\Seeders\UserSeeder;
use Database\Seeders\AssetSeeder;
use Database\Seeders\LicenseSeeder;
use Database\Seeders\ModelTypeSeeder;
use Database\Seeders\CategoryTypeSeeder;
use Database\Seeders\StationaryItemSeeder;
use Database\Seeders\StationaryItemMovementSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            CategoryTypeSeeder::class,
            ModelTypeSeeder::class,
            LicenseSeeder::class,
            AssetSeeder::class,
            StationaryItemSeeder::class,
            StationaryItemMovementSeeder::class,
        ]);
    }
}
