<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class HelloLaravel extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:hello-laravel';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        dd('Hello this is brian');
    }
}
