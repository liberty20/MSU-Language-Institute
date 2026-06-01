<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use App\Models\Department;
use App\Models\MsunliSection;
use App\Models\MsunliRole;

class MsunliHierarchySeeder extends Seeder
{
    public function run()
    {
        // 1. Define LTRDU Sections and Roles
        $ltrdu = Department::where('code', 'LTRDU')->first();
        if ($ltrdu) {
            $sections = [
                'Literature Development' => [
                    'Research Fellow', 'Research Assistant', 'Producer', 'Presenter', 'Assistant Producer', 'Language Researcher', 'Part-Time Staff'
                ],
                'Lexicography' => [
                    'Research Fellow', 'Research Assistant', 'Language Researcher', 'Part-Time Staff'
                ]
            ];
            $this->seedSectionsAndRoles($ltrdu->id, $sections);
        }

        // 2. Define LCSU Sections and Roles
        $lcsu = Department::where('code', 'LCSU')->first();
        if ($lcsu) {
            $sections = [
                'Editorial and Publishing' => [
                    'Publication Editor', 'Part-Time Staff'
                ],
                'Translation' => [
                    'Translation Specialist', 'Translator', 'Part-Time Staff'
                ]
            ];
            $this->seedSectionsAndRoles($lcsu->id, $sections);
        }

        // 3. Define SNSU Sections and Roles
        $snsu = Department::where('code', 'SNSU')->first();
        if ($snsu) {
            $sections = [
                'Braille' => [
                    'Braille Transcriber', 'Special Needs Educator', 'Part-Time Staff'
                ],
                'Sign Language' => [
                    'Sign Language Interpreter', 'Sign Language Educator', 'Part-Time Staff'
                ]
            ];
            $this->seedSectionsAndRoles($snsu->id, $sections);
        }

        // 4. Define ILASU Sections and Roles
        $ilasu = Department::where('code', 'ILASU')->first();
        if ($ilasu) {
            $sections = [
                'Regional Languages' => [
                    'Language Instructor', 'Regional Language Specialist', 'Part-Time Staff'
                ],
                'International Languages' => [
                    'Language Instructor', 'Lecturer', 'International Language Specialist', 'Part-Time Staff'
                ]
            ];
            $this->seedSectionsAndRoles($ilasu->id, $sections);
        }

        // 5. Define AOS Sections and Roles
        $aos = Department::where('code', 'AOS')->first();
        if ($aos) {
            $sections = [
                'Executive and Office Support' => [
                    'Secretary', 'Receptionist'
                ],
                'Operations and Systems' => [
                    'Administrative Assistant', 'ICT Administrator', 'Executive Director', 'Deputy Director'
                ]
            ];
            $this->seedSectionsAndRoles($aos->id, $sections);
        }

        // 6. Assign hierarchy to seeded admin users
        $map = [
            'executive.director@msunli.edu' => ['section' => 'Operations and Systems', 'role' => 'Executive Director'],
            'deputy.director@msunli.edu' => ['section' => 'Operations and Systems', 'role' => 'Deputy Director'],
            'secretary@msunli.edu' => ['section' => 'Executive and Office Support', 'role' => 'Secretary'],
            'receptionist@msunli.edu' => ['section' => 'Executive and Office Support', 'role' => 'Receptionist'],
            'admin.assistant@msunli.edu' => ['section' => 'Operations and Systems', 'role' => 'Administrative Assistant'],
            'admin@msunli.edu' => ['section' => 'Operations and Systems', 'role' => 'ICT Administrator'],
            'parttime@msunli.edu' => ['section' => 'Regional Languages', 'role' => 'Part-Time Staff'],
        ];

        foreach ($map as $email => $info) {
            $user = \App\Models\User::where('email', $email)->first();
            if ($user) {
                $section = MsunliSection::where('name', $info['section'])->first();
                if ($section) {
                    $msRole = MsunliRole::where('section_id', $section->id)->where('name', $info['role'])->first();
                    if ($msRole) {
                        $user->update([
                            'section_id' => $section->id,
                            'msunli_role_id' => $msRole->id
                        ]);
                    }
                }
            }
        }
    }

    private function seedSectionsAndRoles($unitId, $sections)
    {
        foreach ($sections as $sectionName => $roleNames) {
            // Create Section
            $section = MsunliSection::firstOrCreate([
                'unit_id' => $unitId,
                'name' => $sectionName,
                'code' => strtoupper(substr(str_replace(' ', '', $sectionName), 0, 5))
            ]);

            foreach ($roleNames as $roleName) {
                // Ensure a unique Spatie role exists in spatie roles database
                // Convert to snake_case style for Spatie slug names (e.g. research_fellow)
                $spatieRoleSlug = strtolower(str_replace(' ', '_', $roleName));
                if ($spatieRoleSlug === 'administrative_assistant') {
                    $spatieRoleSlug = 'admin_assistant';
                }
                if ($spatieRoleSlug === 'part-time_staff') {
                    $spatieRoleSlug = 'part_time_staff';
                }
                
                $spatieRole = Role::firstOrCreate([
                    'name' => $spatieRoleSlug,
                    'guard_name' => 'web'
                ]);

                // Map in msunli_roles
                MsunliRole::firstOrCreate([
                    'section_id' => $section->id,
                    'role_id' => $spatieRole->id,
                    'name' => $roleName
                ]);
            }
        }
    }
}
