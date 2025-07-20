<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Facades\Tool;
use Prism\Prism\Prism;

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

        $input = $this->ask('How can I help you? (Type "exit" to quit)');

        if (strtolower(trim($input)) === 'exit')
            return $this->info('Goodbye! Have a great day!');

        $weatherTool = Tool::as('weather')
            ->for('Get current weather conditions')
            ->withStringParameter('city', 'The city to get weather for')
            ->using(function (string $city): string {

                $desc = "The weather in {$city} is currently 20°C.";

                return $desc;
            });

        $response = Prism::text()
            ->using(Provider::OpenAI, 'gpt-4o-mini')
            ->withMaxSteps(2)
            ->withPrompt(<<<PROMPT
                You are a weather bot. You can only help with weather-related questions and requests.

                If someone asks about anything other than weather (like math, general knowledge, or other topics), politely respond with:
                "Sorry, I am only a weather bot and can't help with that. I can only provide weather information. Please ask me about weather conditions, forecasts, or weather-related topics."

                User question: {$input}
                PROMPT)
            ->withTools([$weatherTool])
            ->asText();

        $this->line($response->text);
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
