<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\MsunliSection;
use App\Models\MsunliRole;
use Spatie\Permission\Models\Role;

class AddDirectorRolesToOperationsAndSystems extends Migration
{
    public function up()
    {
        $section = MsunliSection::where('name', 'Operations and Systems')->first();
        if ($section) {
            $roles = ['Executive Director', 'Deputy Director'];
            foreach ($roles as $rName) {
                // Find or create spatie role slug
                $slug = strtolower(str_replace(' ', '_', $rName));
                $spRole = Role::firstOrCreate(['name' => $slug, 'guard_name' => 'web']);
                
                // Find or create MSUNLI role mapping
                MsunliRole::firstOrCreate(
                    ['section_id' => $section->id, 'name' => $rName],
                    ['role_id' => $spRole->id]
                );
            }

            // Assign roles and sections to the pre-seeded director users
            $execUser = \App\Models\User::where('email', 'executive.director@msunli.edu')->first();
            if ($execUser) {
                $execRole = MsunliRole::where('section_id', $section->id)->where('name', 'Executive Director')->first();
                if ($execRole) {
                    $execUser->update([
                        'section_id' => $section->id,
                        'msunli_role_id' => $execRole->id
                    ]);
                }
            }

            $deputyUser = \App\Models\User::where('email', 'deputy.director@msunli.edu')->first();
            if ($deputyUser) {
                $deputyRole = MsunliRole::where('section_id', $section->id)->where('name', 'Deputy Director')->first();
                if ($deputyRole) {
                    $deputyUser->update([
                        'section_id' => $section->id,
                        'msunli_role_id' => $deputyRole->id
                    ]);
                }
            }
        }
    }

    public function down()
    {
        $section = MsunliSection::where('name', 'Operations and Systems')->first();
        if ($section) {
            MsunliRole::where('section_id', $section->id)
                ->whereIn('name', ['Executive Director', 'Deputy Director'])
                ->delete();
        }
    }
}
