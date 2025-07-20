<?php

use App\Models\WeatherBotData;
use Illuminate\Console\Command;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

it('can execute weather bot command with weather query', function () {

    $this->artisan('app:weather-bot')
        ->expectsQuestion('How can I help you? (Type "exit" to quit)', 'What\'s the weather where I am located?')
        ->expectsQuestion('Where are you located?', 'London')
        ->expectsOutputToContain('The weather in London is currently')
        ->assertExitCode(0);
});

it('can execute weather bot command with non-weather query', function () {
    
    $this->artisan('app:weather-bot')
        ->expectsQuestion('How can I help you? (Type "exit" to quit)', 'Who is the president of the United States?')
        ->expectsQuestion('Where are you located?', 'London')
        ->expectsOutput('Sorry, I am only a weather bot and can\'t help with that. I can only provide weather information. Please ask me about weather conditions, forecasts, or weather-related topics.')
        ->assertExitCode(0);
});