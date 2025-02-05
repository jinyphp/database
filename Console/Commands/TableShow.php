<?php
namespace Jiny\Database\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TableShow extends Command
{
    // Command signature and description
    protected $signature = 'table:show {table}';
    protected $description = 'Display the schema of a specified table';

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

        // Retrieve and display the schema information for the table
        $columns = Schema::getColumnListing($table);
        $this->info("Schema for table: {$table}");
        $this->line('-----------------------------');

        foreach ($columns as $column) {
            // Get column type
            $columnType = Schema::getColumnType($table, $column);

            // // Default values and nullable status
            // $isNullable = $this->isColumnNullable($table, $column);
            // $defaultValue = $this->getColumnDefault($table, $column);

            // Display the column details
            $this->line("Column: {$column}");
            $this->line("Type: {$columnType}");
            // $this->line("Nullable: " . ($isNullable ? 'Yes' : 'No'));
            // $this->line("Default: " . ($defaultValue ?: 'N/A'));
            $this->line("-----------------------------");
        }
    }


}
