<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class WeatherBot extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:weather-bot';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Interactive weather bot that provides weather information';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->welcomeUser();
    }

     /**
     * Welcome the user to the weather bot
     */
    private function welcomeUser()
    {
        $this->info('🌤️  Welcome to WeatherBot! 🌤️');
        $this->line('');
    }
}
