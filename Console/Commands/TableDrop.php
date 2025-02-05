<?php
namespace Jiny\Database\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TableDrop extends Command
{
    // The name and signature of the console command
    protected $signature = 'table:drop {table}';

    // Description of the command
    protected $description = 'Delete a specific table and remove the associated migration from the migrations table';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $table = $this->argument('table');

        // Check if the table exists in the database
        if (!Schema::hasTable($table)) {
            $this->error("Table '{$table}' does not exist.");
            return;
        }

        // Find the corresponding migration record in the migrations table
        $migration = $this->findMigrationForTable($table);

        if (!$migration) {
            $this->error("No migration entry found for table '{$table}' in the migrations table.");
            return;
        }

        // Ask for confirmation before deleting
        if ($this->confirm("Are you sure you want to delete the '{$table}' table and its migration record?", false)) {
            // Drop the table
            Schema::dropIfExists($table);
            $this->info("Table '{$table}' deleted.");

            // Delete the corresponding migration entry from the migrations table
            DB::table('migrations')->where('migration', $migration->migration)->delete();
            $this->info("Migration entry '{$migration->migration}' removed from the migrations table.");
        } else {
            $this->info('Operation cancelled.');
        }
    }

    // Find the migration for the table by matching the migration name in the migrations table
    protected function findMigrationForTable($table)
    {
        // We assume migration names are standardized, e.g., 'create_site_help_table'
        $migrationName = 'create_' . $table . '_table';

        // Look for a migration name that contains the table name in the migrations table
        return DB::table('migrations')
            ->where('migration', 'LIKE', "%{$migrationName}%")
            ->first();
    }
}

