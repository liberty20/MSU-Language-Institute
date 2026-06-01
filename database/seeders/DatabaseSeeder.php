<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            UnitsSeeder::class,
            MsunliHierarchySeeder::class,
            DummyDataSeeder::class,
            CourseDummySeeder::class,
            SystemSettingsSeeder::class,
        ]);
    }
}
