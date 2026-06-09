<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $temp = \App\Services\UserBackupService::$shouldBackup;
        \App\Services\UserBackupService::$shouldBackup = false;

        try {
            $this->call([
                RolesAndPermissionsSeeder::class,
                UnitsSeeder::class,
                MsunliHierarchySeeder::class,
                // DummyDataSeeder::class,
                CourseDummySeeder::class,
                SystemSettingsSeeder::class,
            ]);
        } finally {
            \App\Services\UserBackupService::$shouldBackup = $temp;
        }
    }
}
