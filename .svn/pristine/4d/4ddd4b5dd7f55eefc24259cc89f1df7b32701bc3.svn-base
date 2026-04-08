<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;

class ExportDatabase extends Command
{
    protected $signature = 'export:database';

    protected $description = 'Export the database to a .sql file';

    public function handle()
    {
        $username = 'root';
        $password = env('DB_PASSWORD');
        $database = env('DB_DATABASE');
        $host = env('DB_HOST');
        $filename = 'backup.sql';

        shell_exec("mysqldump --user={$username} --password={$password} --host={$host} --databases {$database} > {$filename}");
    }
}
