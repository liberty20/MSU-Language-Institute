<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CopySqliteToMysql extends Command
{
    protected $signature = 'db:copy-sqlite-to-mysql
        {--sqlite= : Path to the SQLite database file}
        {--connection=mysql : Target MySQL connection name}
        {--fresh : Run migrate:fresh on the target before copying data}
        {--include-migrations : Copy the migrations table too}
        {--dry-run : Show source table counts without writing to MySQL}
        {--force : Required when running in production}';

    protected $description = 'Copy data from the SQLite database file into the configured MySQL database.';

    public function handle()
    {
        if (app()->environment('production') && !$this->option('force')) {
            $this->error('Refusing to run in production without --force.');
            return self::FAILURE;
        }

        $sqlitePath = $this->option('sqlite') ?: database_path('database.sqlite');
        $sqlitePath = $this->normalizePath($sqlitePath);

        if (!is_file($sqlitePath)) {
            $this->error("SQLite database not found: {$sqlitePath}");
            return self::FAILURE;
        }

        $targetConnection = $this->option('connection') ?: 'mysql';
        if (Config::get("database.connections.{$targetConnection}.driver") !== 'mysql') {
            $this->error("Target connection [{$targetConnection}] is not a MySQL connection.");
            return self::FAILURE;
        }

        $this->configureSourceConnection($sqlitePath);

        $tables = $this->sourceTables();
        if (!$this->option('include-migrations')) {
            $tables = array_values(array_diff($tables, ['migrations']));
        }

        if (empty($tables)) {
            $this->warn('No SQLite tables found to copy.');
            return self::SUCCESS;
        }

        $this->line("Source SQLite: {$sqlitePath}");
        $this->line("Target MySQL connection: {$targetConnection}");

        $counts = $this->sourceCounts($tables);
        $this->table(['Table', 'Rows'], $counts);

        if ($this->option('dry-run')) {
            $this->info('Dry run complete. No MySQL data was changed.');
            return self::SUCCESS;
        }

        if ($this->option('fresh')) {
            $this->warn("Running migrate:fresh on [{$targetConnection}] before import.");
            Artisan::call('migrate:fresh', [
                '--database' => $targetConnection,
                '--force' => true,
            ]);
            $this->output->write(Artisan::output());
        }

        $this->copyTables($tables, $targetConnection);

        try {
            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        } catch (\Throwable $exception) {
            $this->warn('Copied data, but could not clear the permission cache: '.$exception->getMessage());
        }

        $this->info('SQLite data copied to MySQL successfully.');
        return self::SUCCESS;
    }

    private function normalizePath(string $path): string
    {
        if (preg_match('/^[A-Za-z]:[\\\\\\/]/', $path) || str_starts_with($path, DIRECTORY_SEPARATOR)) {
            return $path;
        }

        return base_path($path);
    }

    private function configureSourceConnection(string $sqlitePath): void
    {
        Config::set('database.connections.sqlite_import_source', [
            'driver' => 'sqlite',
            'database' => $sqlitePath,
            'prefix' => '',
            'foreign_key_constraints' => false,
        ]);

        DB::purge('sqlite_import_source');
    }

    private function sourceTables(): array
    {
        $rows = DB::connection('sqlite_import_source')->select(
            "select name from sqlite_master where type = 'table' and name not like 'sqlite_%' order by name"
        );

        return array_map(function ($row) {
            return $row->name;
        }, $rows);
    }

    private function sourceCounts(array $tables): array
    {
        return array_map(function ($table) {
            return [
                'table' => $table,
                'rows' => DB::connection('sqlite_import_source')->table($table)->count(),
            ];
        }, $tables);
    }

    private function copyTables(array $tables, string $targetConnection): void
    {
        $target = DB::connection($targetConnection);
        $source = DB::connection('sqlite_import_source');
        $targetSchema = Schema::connection($targetConnection);

        $target->statement('SET FOREIGN_KEY_CHECKS=0');

        try {
            foreach ($tables as $table) {
                if (!$targetSchema->hasTable($table)) {
                    $this->warn("Skipping [{$table}] because it does not exist on the MySQL target.");
                    continue;
                }

                $columns = $targetSchema->getColumnListing($table);
                $columnMap = array_flip($columns);

                $target->table($table)->truncate();

                $copied = 0;
                $source->table($table)->orderBy($this->orderColumn($table))->chunk(500, function ($rows) use ($target, $table, $columnMap, &$copied) {
                    $batch = [];

                    foreach ($rows as $row) {
                        $batch[] = array_intersect_key((array) $row, $columnMap);
                    }

                    foreach (array_chunk($batch, 100) as $chunk) {
                        if (!empty($chunk)) {
                            $target->table($table)->insert($chunk);
                            $copied += count($chunk);
                        }
                    }
                });

                $this->line("Copied {$copied} rows into {$table}.");
            }
        } finally {
            $target->statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    private function orderColumn(string $table): string
    {
        $columns = Schema::connection('sqlite_import_source')->getColumnListing($table);

        return in_array('id', $columns, true) ? 'id' : $columns[0];
    }
}
