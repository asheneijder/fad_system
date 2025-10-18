<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Database\Seeders\CategorySeeder;
use Database\Seeders\LicensesSeeder;
use Database\Seeders\ModelTypeSeeder;
use Database\Seeders\StationaryItemsSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            CategorySeeder::class,
            LicensesSeeder::class,
            ModelTypeSeeder::class,
            StationaryItemsSeeder::class,
        ]);
    }
}
