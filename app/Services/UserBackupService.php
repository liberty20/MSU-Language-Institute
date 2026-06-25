<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class UserBackupService
{
    public static $shouldBackup = true;
    public static $isSyncing = false;
    protected static $hasBackedUp = false;
    protected static $hasRestored = false;

    protected static $tables = [
        'departments',
        'msunli_sections',
        'msunli_roles',
        'permissions',
        'roles',
        'role_has_permissions',
        'users',
        'model_has_roles',
        'model_has_permissions',
        'clients',
        'service_requests',
        'quotations',
        'approvals',
        'assignments',
        'tasks',
        'payments',
        'activity_logs',
        'uploaded_documents',
        'courses',
        'course_intakes',
        'course_applications',
        'course_application_logs',
        'course_enrollments',
        'course_timetables',
        'course_assignments',
        'course_assignment_submissions',
        'course_ca_marks',
        'system_settings',
        'bank_accounts',
        'procurement_requests',
        'kpi_records',
        'staff_schedules',
        'comments',
        'reports',
        'email_logs',
        'learning_contents',
        'messages',
        'notices',
        'announcements',
        'announcement_reads',
    ];

    /**
     * Get the backup file path, resolving to a test file name if running unit tests.
     */
    public static function getBackupFilePath()
    {
        if (app()->runningUnitTests()) {
            return storage_path('app/user_backup_test.json');
        }
        return storage_path('app/user_backup.json');
    }

    /**
     * Backup critical database tables to a JSON file.
     */
    public static function backup($force = false)
    {
        if (!self::$shouldBackup) {
            return;
        }

        // Prevent backups during console commands (migrations, seeders, etc.)
        // to avoid overwriting the backup JSON with partial or empty data.
        if (app()->runningInConsole() && !app()->runningUnitTests()) {
            return;
        }

        if (!$force && self::$hasBackedUp) {
            return;
        }
        self::$hasBackedUp = true;

        $temp = self::$shouldBackup;
        self::$shouldBackup = false;

        try {
            $backupData = [];

            foreach (self::$tables as $table) {
                if (Schema::hasTable($table)) {
                    $backupData[$table] = DB::table($table)->get()->map(function ($row) {
                        return (array) $row;
                    })->toArray();
                }
            }

            $filePath = self::getBackupFilePath();
            $directory = dirname($filePath);
            if (!is_dir($directory)) {
                mkdir($directory, 0755, true);
            }

            file_put_contents($filePath, json_encode($backupData, JSON_PRETTY_PRINT));
            Log::info("User database backup successfully written to: " . $filePath);
        } catch (\Exception $e) {
            Log::error("Failed to backup user database: " . $e->getMessage());
        } finally {
            self::$shouldBackup = $temp;
        }
    }

    /**
     * Restore critical database tables from the JSON backup file.
     */
    public static function restore($force = false, $merge = true)
    {
        if (!$force && self::$hasRestored) {
            return;
        }
        self::$hasRestored = true;

        $temp = self::$shouldBackup;
        self::$shouldBackup = false;

        try {
            $filePath = self::getBackupFilePath();
            if (!file_exists($filePath)) {
                Log::warning("Backup file not found at: " . $filePath);
                return;
            }

            $backupData = json_decode(file_get_contents($filePath), true);
            if (!is_array($backupData)) {
                Log::error("Invalid backup data format in " . $filePath);
                return;
            }

            Schema::disableForeignKeyConstraints();

            DB::transaction(function () use ($backupData, $merge) {
                foreach (self::$tables as $table) {
                    if (!isset($backupData[$table]) || !Schema::hasTable($table)) {
                        continue;
                    }

                    if (!$merge) {
                        // Empty the table first to ensure a clean restore (overwrites/deletes old records)
                        DB::table($table)->truncate();
                    }

                    $columns = Schema::getColumnListing($table);
                    $columnsFlip = array_flip($columns);

                    foreach ($backupData[$table] as $record) {
                        // Filter record keys to only include columns that exist in the table schema
                        $record = array_intersect_key($record, $columnsFlip);

                        $keys = [];
                        if ($table === 'model_has_roles') {
                            $keys = [
                                'role_id' => $record['role_id'] ?? null,
                                'model_type' => $record['model_type'] ?? null,
                                'model_id' => $record['model_id'] ?? null
                            ];
                        } elseif ($table === 'role_has_permissions') {
                            $keys = [
                                'permission_id' => $record['permission_id'] ?? null,
                                'role_id' => $record['role_id'] ?? null
                            ];
                        } elseif ($table === 'model_has_permissions') {
                            $keys = [
                                'permission_id' => $record['permission_id'] ?? null,
                                'model_type' => $record['model_type'] ?? null,
                                'model_id' => $record['model_id'] ?? null
                            ];
                        } else {
                            $keys = ['id' => $record['id'] ?? null];
                        }

                        // Ensure keys do not contain nulls
                        if (in_array(null, $keys, true)) {
                            continue;
                        }

                        DB::table($table)->updateOrInsert($keys, $record);
                    }
                }
            });

            Schema::enableForeignKeyConstraints();

            // Clear Spatie Permission Cache to apply restored roles/permissions immediately
            try {
                app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
            } catch (\Exception $ex) {
                Log::warning("Could not clear Spatie permission cache during restore: " . $ex->getMessage());
            }

            Log::info("User database successfully restored from: " . $filePath);
        } catch (\Exception $e) {
            Schema::enableForeignKeyConstraints();
            Log::error("Failed to restore user database: " . $e->getMessage());
        } finally {
            self::$shouldBackup = $temp;
        }
    }

    /**
     * Synchronize a Client record changes to its corresponding User.
     *
     * @param \App\Models\Client $client
     * @return void
     */
    public static function syncFromClient(\App\Models\Client $client)
    {
        if (self::$isSyncing) {
            return;
        }

        self::$isSyncing = true;

        try {
            $user = null;
            if ($client->user_id) {
                $user = \App\Models\User::find($client->user_id);
            }
            if (!$user) {
                $user = \App\Models\User::whereEmail($client->email)->first();
            }

            $name = $client->organization ?: $client->contact_person;

            if ($user) {
                $user->update([
                    'name'      => $name,
                    'email'     => $client->email,
                    'phone'     => $client->phone,
                    'is_active' => $client->status === 'active',
                ]);
            } else {
                $user = \App\Models\User::create([
                    'name'      => $name,
                    'email'     => $client->email,
                    'phone'     => $client->phone,
                    'password'  => \Illuminate\Support\Facades\Hash::make('password'),
                    'is_active' => $client->status === 'active',
                ]);
            }

            // Ensure the user has the client role
            if (!$user->hasRole('client')) {
                $user->assignRole('client');
            }

            // Sync user_id back if not set
            if ($client->user_id !== $user->id) {
                $client->user_id = $user->id;
            }
        } catch (\Exception $e) {
            Log::error("UserBackupService error in syncFromClient: " . $e->getMessage());
        } finally {
            self::$isSyncing = false;
        }
    }

    /**
     * Delete the corresponding User for a Client.
     *
     * @param \App\Models\Client $client
     * @return void
     */
    public static function deleteUserForClient(\App\Models\Client $client)
    {
        if (self::$isSyncing) {
            return;
        }

        self::$isSyncing = true;

        try {
            $user = null;
            if ($client->user_id) {
                $user = \App\Models\User::find($client->user_id);
            }
            if (!$user) {
                $user = \App\Models\User::whereEmail($client->email)->first();
            }

            if ($user) {
                $user->delete();
            }
        } catch (\Exception $e) {
            Log::error("UserBackupService error in deleteUserForClient: " . $e->getMessage());
        } finally {
            self::$isSyncing = false;
        }
    }

    /**
     * Synchronize a User record changes to its corresponding Client.
     *
     * @param \App\Models\User $user
     * @return void
     */
    public static function syncFromUser(\App\Models\User $user)
    {
        if (self::$isSyncing) {
            return;
        }

        // If the user does not have the client role, we check if they have a client record and delete it
        if (!$user->hasRole('client')) {
            self::deleteClientForUser($user);
            return;
        }

        self::$isSyncing = true;

        try {
            $client = \App\Models\Client::whereUserId($user->id)->first();
            if (!$client) {
                $client = \App\Models\Client::whereEmail($user->email)->first();
            }

            $clientData = [
                'user_id' => $user->id,
                'email'   => $user->email,
                'phone'   => $user->phone,
                'status'  => $user->is_active ? 'active' : 'inactive',
            ];

            if ($client) {
                if ($client->client_type === 'organization') {
                    $clientData['organization'] = $user->name;
                } else {
                    $clientData['contact_person'] = $user->name;
                }
                $client->update($clientData);
            } else {
                $clientData['client_type']    = 'individual';
                $clientData['contact_person'] = $user->name;
                \App\Models\Client::create($clientData);
            }
        } catch (\Exception $e) {
            Log::error("UserBackupService error in syncFromUser: " . $e->getMessage());
        } finally {
            self::$isSyncing = false;
        }
    }

    /**
     * Delete the corresponding Client for a User.
     *
     * @param \App\Models\User $user
     * @return void
     */
    public static function deleteClientForUser(\App\Models\User $user)
    {
        if (self::$isSyncing) {
            return;
        }

        self::$isSyncing = true;

        try {
            $client = \App\Models\Client::whereUserId($user->id)->first();
            if (!$client) {
                $client = \App\Models\Client::whereEmail($user->email)->first();
            }

            if ($client) {
                $client->delete();
            }
        } catch (\Exception $e) {
            Log::error("UserBackupService error in deleteClientForUser: " . $e->getMessage());
        } finally {
            self::$isSyncing = false;
        }
    }
}

