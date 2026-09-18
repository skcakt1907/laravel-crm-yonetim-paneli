<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class OptimizeCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'optimize:all';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Optimize all Laravel caches for better performance';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting optimization...');
        
        // Config cache
        $this->info('Caching configuration...');
        $this->call('config:cache');
        
        // Route cache
        $this->info('Caching routes...');
        $this->call('route:cache');
        
        // View cache
        $this->info('Caching views...');
        $this->call('view:cache');
        
        // Event cache
        $this->info('Caching events...');
        $this->call('event:cache');
        
        $this->info('Optimization completed!');
        
        return 0;
    }
}
