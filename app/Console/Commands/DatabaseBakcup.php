<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class DatabaseBakcup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'database:backup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Database backup';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $filename = 'database-' . now()->format('Y-m-d') . '.sql';
        $command = "mysqldump --user=" . env('DB_USERNAME') . " --password=" . env('DB_PASSWORD') . " --host=" . env('DB_HOST') . " --port=" . env('DB_PORT') . " " . env('DB_DATABASE') . " > " . storage_path() . "/app/backup/" . $filename;

        $returnVar = null;
        $output = null;
        exec($command, $output, $returnVar);

    }
}
