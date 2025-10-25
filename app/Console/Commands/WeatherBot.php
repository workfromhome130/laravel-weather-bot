<?php

namespace App\Console\Commands;

use App\Models\WeatherBotData;
use GuzzleHttp\Client;
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

        if (strtolower(trim($input)) === 'exit') {
            return $this->info('Good-bye! Have a great day!');
        }

        $ip = $this->getClientIP();

        if (!WeatherBotData::where('ip', $ip)->exists()) {
            $location = $this->ask('Where are you located?');
            WeatherBotData::create([
                'ip' => $ip,
                'location' => $location,
            ]);
        }
        $location = WeatherBotData::where('ip', $ip)->first()->location;

        $weatherTool = Tool::as('weather')
            ->for('Get current weather conditions')
            ->withStringParameter('city', 'The city to get weather for')
            ->using(function (string $city): string {
                // Get coordinates for the city
                $coordinates = $this->getCityCoordinates($city);

                if (!$coordinates) {
                    return "Sorry, I couldn't find the city \"{$city}\".";
                }

                // Get weather forecast using coordinates
                $weatherData = $this->getWeatherForecast($coordinates['lat'], $coordinates['lon']);

                if (!$weatherData) {
                    return "Sorry, I couldn't retrieve the weather for {$coordinates['cityName']}.";
                }

                $weather = $weatherData['current_weather'];
                $temperature = $weather['temperature'];

                return "The weather (temperature) in {$coordinates['cityName']} is currently {$temperature}°C.";
            });

        $response = Prism::text()
            ->using(Provider::OpenAI, 'gpt-4o-mini')
            ->withMaxSteps(2)
            ->withPrompt(<<<PROMPT
                You are a weather bot. You can only help with weather-related questions and requests.
                Respond in this format: The weather in {city} is currently {temperature}°C.

                If someone asks about anything other than weather (like math, general knowledge, or other topics), politely respond with:
                "Sorry, I am only a weather bot and can't help with that. I can only provide weather information. Please ask me about weather conditions, forecasts, or weather-related topics."

                User question: {$input} {$location}
                PROMPT)
            ->withTools([$weatherTool])
            ->asText();

        $this->line($response->text);
    }

    /**
     * Get coordinates for a given city name
     *
     * @param string $city
     * @return array|null Returns array with lat, lon, cityName, country or null if not found
     */
    private function getCityCoordinates(string $city): ?array
    {
        // $http = new \GuzzleHttp\Client();
        $http = new Client();

        try {
            $geoResponse = $http->get('https://geocoding-api.open-meteo.com/v1/search', [
                'query' => [
                    'name' => $city,
                    'count' => 1,
                    'language' => 'en',
                    'format' => 'json',
                ]
            ]);

            $geoData = json_decode($geoResponse->getBody(), true);

            if (empty($geoData['results'][0])) {
                return null;
            }

            $result = $geoData['results'][0];

            return [
                'lat' => $result['latitude'],
                'lon' => $result['longitude'],
                'cityName' => $result['name'],
            ];
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get weather forecast for given coordinates
     *
     * @param float $latitude
     * @param float $longitude
     * @return array|null Returns weather data or null if failed
     */
    private function getWeatherForecast(float $latitude, float $longitude): ?array
    {
        $http = new \GuzzleHttp\Client();

        try {
            $weatherResponse = $http->get('https://api.open-meteo.com/v1/forecast', [
                'query' => [
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'current_weather' => 'true',
                    'temperature_unit' => 'celsius',
                ]
            ]);

            $weatherData = json_decode($weatherResponse->getBody(), true);

            if (empty($weatherData['current_weather'])) {
                return null;
            }

            return $weatherData;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get the client's real IP address using an external API
     *
     * @return string
     */
    private function getClientIP(): string
    {
        // $http = new \GuzzleHttp\Client();
        $http = new Client();

        try {
            $response = $http->get('https://api.ipify.org?format=json');
            $data = json_decode($response->getBody(), true);

            return $data['ip'] ?? 'Unknown';
        } catch (\Exception $e) {
            // Fallback to alternative API
            try {
                $response = $http->get('https://httpbin.org/ip');
                $data = json_decode($response->getBody(), true);

                return $data['origin'] ?? 'Unknown';
            } catch (\Exception $e) {
                return 'Unknown';
            }
        }
    }

    /**
     * Welcome the user to the weather bot
     */
    private function welcomeUser()
    {
        $this->info('🌤️   Welcome to WeatherBot! 🌤️');
        $this->line('');
    }
}
