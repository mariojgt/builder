<?php

namespace Mariojgt\Builder\Commands;

use Artisan;
use Illuminate\Console\Command;

class Install extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'install:builder';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This Command will install Builder package';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // Copy the need file to make the Builder to work
        Artisan::call('vendor:publish', [
            '--provider' => 'Mariojgt\Builder\BuilderProvider',
            '--force' => true,
        ]);

        $this->newLine();
        $this->info('The command was successful!');
    }
}
