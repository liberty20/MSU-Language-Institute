<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class AddPrimaryCategoryToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 1. Add column to users table
        if (!Schema::hasColumn('users', 'primary_category')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('primary_category')->default('Staff')->after('email');
            });
        }

        // Disable model event listeners to avoid sync loops during migration
        $tempBackup = \App\Services\UserBackupService::$shouldBackup;
        \App\Services\UserBackupService::$shouldBackup = false;

        try {
            // 2. Audit and correct existing user records
            $users = User::all();

            foreach ($users as $user) {
                // Fetch Spatie role names for this user directly from database
                $roles = DB::table('model_has_roles')
                    ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                    ->where('model_has_roles.model_id', $user->id)
                    ->where('model_has_roles.model_type', User::class)
                    ->pluck('roles.name')
                    ->toArray();

                // Detect category based on hierarchical fields & roles
                $isStaff = $user->department_id !== null || $user->msunli_role_id !== null || $user->section_id !== null;

                // Explicit check for Mukudzei Toro (should be Staff)
                $isToro = strpos(strtolower($user->name), 'mukudzei') !== false || strpos(strtolower($user->name), 'toro') !== false;

                if ($isToro || $isStaff) {
                    $category = 'Staff';
                } elseif (in_array('client', $roles)) {
                    $category = 'Client';
                } elseif (in_array('student', $roles)) {
                    $category = 'Student';
                } else {
                    $category = 'Staff';
                }

                // Update category in DB
                DB::table('users')->where('id', $user->id)->update([
                    'primary_category' => $category
                ]);

                // Enforce Spatie role integrity based on the detected category
                if ($category === 'Staff') {
                    // Detach student and client roles
                    $rolesToDetach = DB::table('roles')->whereIn('name', ['student', 'client'])->pluck('id')->toArray();
                    if (!empty($rolesToDetach)) {
                        DB::table('model_has_roles')
                            ->where('model_id', $user->id)
                            ->where('model_type', User::class)
                            ->whereIn('role_id', $rolesToDetach)
                            ->delete();
                    }

                    // Check if they have any remaining staff roles
                    $hasStaffRole = DB::table('model_has_roles')
                        ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                        ->where('model_has_roles.model_id', $user->id)
                        ->where('model_has_roles.model_type', User::class)
                        ->whereNotIn('roles.name', ['student', 'client'])
                        ->exists();

                    // If they have no roles left, assign a default staff role (e.g. language_expert)
                    if (!$hasStaffRole) {
                        $expertRole = DB::table('roles')->where('name', 'language_expert')->first();
                        if ($expertRole) {
                            DB::table('model_has_roles')->insertOrIgnore([
                                'role_id' => $expertRole->id,
                                'model_type' => User::class,
                                'model_id' => $user->id
                            ]);
                        }
                    }
                } elseif ($category === 'Student') {
                    // Ensure they ONLY have the student role
                    $studentRole = DB::table('roles')->where('name', 'student')->first();
                    if ($studentRole) {
                        DB::table('model_has_roles')
                            ->where('model_id', $user->id)
                            ->where('model_type', User::class)
                            ->where('role_id', '!=', $studentRole->id)
                            ->delete();

                        DB::table('model_has_roles')->insertOrIgnore([
                            'role_id' => $studentRole->id,
                            'model_type' => User::class,
                            'model_id' => $user->id
                        ]);
                    }
                } elseif ($category === 'Client') {
                    // Ensure they ONLY have the client role
                    $clientRole = DB::table('roles')->where('name', 'client')->first();
                    if ($clientRole) {
                        DB::table('model_has_roles')
                            ->where('model_id', $user->id)
                            ->where('model_type', User::class)
                            ->where('role_id', '!=', $clientRole->id)
                            ->delete();

                        DB::table('model_has_roles')->insertOrIgnore([
                            'role_id' => $clientRole->id,
                            'model_type' => User::class,
                            'model_id' => $user->id
                        ]);
                    }
                }
            }

            // Force database backup sync with corrected records
            \App\Services\UserBackupService::backup(true);

        } finally {
            \App\Services\UserBackupService::$shouldBackup = $tempBackup;
        }

        // 3. Add database CHECK constraint on MySQL/MariaDB
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE users ADD CONSTRAINT chk_user_category CHECK (primary_category IN ('Staff', 'Student', 'Client'))");
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            try {
                DB::statement("ALTER TABLE users DROP CONSTRAINT chk_user_category");
            } catch (\Exception $e) {
                // Ignore if constraint doesn't exist
            }
        }

        if (Schema::hasColumn('users', 'primary_category')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('primary_category');
            });
        }
    }
}
