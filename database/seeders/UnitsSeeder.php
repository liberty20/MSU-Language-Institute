<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UnitsSeeder extends Seeder
{
    public function run()
    {
        $units = [
            [
                'name' => 'Language Technology and Resource Development Unit',
                'code' => 'LTRDU',
                'head_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Language Consultancy and Services Unit',
                'code' => 'LCSU',
                'head_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Special Needs Services Unit',
                'code' => 'SNSU',
                'head_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'International Languages and Area Studies Unit',
                'code' => 'ILASU',
                'head_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Administration and Operations Support',
                'code' => 'AOS',
                'head_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($units as $unit) {
            DB::table('departments')->updateOrInsert(
                ['code' => $unit['code']],
                $unit
            );
        }

        // Assign departments to expert, secretary, and other users
        $aos_unit = DB::table('departments')->where('code', 'AOS')->first();
        if ($aos_unit) {
            $adminUsers = [
                'admin.assistant@msunli.edu',
                'secretary@msunli.edu',
                'receptionist@msunli.edu',
                'admin@msunli.edu'
            ];
            foreach ($adminUsers as $email) {
                $u = \App\Models\User::where('email', $email)->first();
                if ($u) {
                    $u->update(['department_id' => $aos_unit->id]);
                }
            }
        }

        $expert = \App\Models\User::where('email', 'expert@msunli.edu')->first();
        if ($expert) {
            $sns_unit = DB::table('departments')->where('code', 'SNSU')->first();
            if ($sns_unit) {
                $expert->update(['department_id' => $sns_unit->id]);
            }
        }

        $parttime = \App\Models\User::where('email', 'parttime@msunli.edu')->first();
        if ($parttime) {
            $ilas_unit = DB::table('departments')->where('code', 'ILASU')->first();
            if ($ilas_unit) {
                $parttime->update(['department_id' => $ilas_unit->id]);
            }
        }
    }
}
