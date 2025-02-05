<?php
namespace Jiny\Laravel\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class BackupSeeder extends Command
{
    // Command signature and description
    protected $signature = 'backup:seeder {table}';
    protected $description = 'Create a database seeder for a given table with existing data';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $table = $this->argument('table');

        // Generate the Seeder class name based on the table name
        // $seederClassName = ucfirst(camel_case($table)) . 'Seeder';
        $seederClassName = Str::studly($table) . 'Seeder';

        // Define the path to the Seeder file
        $path = database_path('seeders/' . $seederClassName . '.php');

        // Check if the seeder file already exists
        if (File::exists($path)) {
            // Ask the user whether they want to overwrite the file
            if ($this->confirm('Seeder already exists. Do you want to overwrite it? (y/n)', false)) {
                // Delete the existing seeder file
                File::delete($path);
                $this->info('Old seeder file deleted.');
            } else {
                // Cancel the operation if the user selects "n"
                $this->info('Operation canceled.');
                return;
            }
        }

        // Fetch the data from the table
        $data = DB::table($table)->get()->toArray();

        // If no data exists in the table, notify the user
        if (empty($data)) {
            $this->error("No data found in table '{$table}'.");
            return;
        }

        // Generate the Seeder class content with the backed-up data
        $seederContent = $this->generateSeederContent($seederClassName, $table, $data);

        // Write the Seeder file
        File::put($path, $seederContent);

        $this->info('Seeder created successfully: ' . $seederClassName);
        $this->info('php artisan db:seed --class=' . $seederClassName);

    }

    // Generate Seeder class content
    protected function generateSeederContent($seederClassName, $table, $data)
    {
        // Convert the table data into a string representation of a PHP array
        $dataString = $this->formatDataAsArrayString($data);

        return <<<EOD
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class {$seederClassName} extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('{$table}')->insert($dataString);
    }
}

EOD;
    }

    // Convert the data into a formatted PHP array string
    protected function formatDataAsArrayString($data)
    {
        // Convert each object to an associative array
        $formattedData = array_map(fn($item) => (array)$item, $data);

        // Convert array to string with correct formatting
        return var_export($formattedData, true);
    }
}

