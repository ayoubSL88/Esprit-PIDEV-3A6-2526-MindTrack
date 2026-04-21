<?php

namespace App\Service\Habitude;

final class OpenMeteoWeatherService
{
    public function __construct(
        private readonly float $latitude,
        private readonly float $longitude,
        private readonly string $locationLabel,
    ) {
    }

    /**
     * @return array{
     *     available: bool,
     *     locationLabel: string,
     *     temperature: ?float,
     *     windSpeed: ?float,
     *     windDirection: ?int,
     *     weatherCode: ?int,
     *     observedAt: ?string,
     *     condition: string,
     *     icon: string,
     *     theme: string,
     *     advice: string,
     *     isDay: bool
     * }
     */
    public function getCurrentWeather(): array
    {
        $fallback = [
            'available' => false,
            'locationLabel' => $this->locationLabel,
            'temperature' => null,
            'humidity' => null,
            'windSpeed' => null,
            'windDirection' => null,
            'weatherCode' => null,
            'observedAt' => null,
            'condition' => 'Meteo indisponible',
            'icon' => '::',
            'theme' => 'calm',
            'advice' => 'La meteo reviendra bientot. Continuez vos habitudes avec votre rythme habituel.',
            'isDay' => true,
        ];

        try {
            $url = sprintf(
                'https://api.open-meteo.com/v1/forecast?latitude=%s&longitude=%s&current_weather=true&timezone=auto',
                rawurlencode((string) $this->latitude),
                rawurlencode((string) $this->longitude)
            );

            $context = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'timeout' => 8,
                    'ignore_errors' => true,
                    'header' => "Accept: application/json\r\nUser-Agent: MindTrackWeather/1.0\r\n",
                ],
            ]);

            $rawResponse = @file_get_contents($url, false, $context);
            if ($rawResponse === false) {
                return $fallback;
            }

            /** @var array{current_weather?: array<string, mixed>} $data */
            $data = json_decode($rawResponse, true, 512, JSON_THROW_ON_ERROR);
            $currentWeather = $data['current_weather'] ?? [];

            if ($currentWeather === []) {
                return $fallback;
            }

            $weatherCode = isset($currentWeather['weathercode']) ? (int) $currentWeather['weathercode'] : null;
            $isDay = ((int) ($currentWeather['is_day'] ?? 1)) === 1;
            $presentation = $this->describeWeather($weatherCode, $isDay);

            return [
                'available' => true,
                'locationLabel' => $this->locationLabel,
                'temperature' => isset($currentWeather['temperature']) ? (float) $currentWeather['temperature'] : null,
                'humidity' => null,
                'windSpeed' => isset($currentWeather['windspeed']) ? (float) $currentWeather['windspeed'] : null,
                'windDirection' => isset($currentWeather['winddirection']) ? (int) $currentWeather['winddirection'] : null,
                'weatherCode' => $weatherCode,
                'observedAt' => isset($currentWeather['time']) ? (string) $currentWeather['time'] : null,
                'condition' => $presentation['condition'],
                'icon' => $presentation['icon'],
                'theme' => $presentation['theme'],
                'advice' => $presentation['advice'],
                'isDay' => $isDay,
            ];
        } catch (\JsonException|\TypeError) {
            return $fallback;
        }
    }

    /**
     * @return array{condition: string, icon: string, theme: string, advice: string}
     */
    private function describeWeather(?int $weatherCode, bool $isDay): array
    {
        return match ($weatherCode) {
            0 => [
                'condition' => $isDay ? 'Ciel degage' : 'Nuit claire',
                'icon' => $isDay ? 'Sun' : 'Moon',
                'theme' => 'sunny',
                'advice' => 'Bonne energie pour lancer vos habitudes prioritaires des maintenant.',
            ],
            1, 2 => [
                'condition' => 'Partiellement nuageux',
                'icon' => 'CloudSun',
                'theme' => 'soft',
                'advice' => 'Un temps stable pour garder un rythme regulier sur toute la journee.',
            ],
            3 => [
                'condition' => 'Couvert',
                'icon' => 'Cloud',
                'theme' => 'calm',
                'advice' => 'Misez sur des habitudes simples et faciles a cocher rapidement.',
            ],
            45, 48 => [
                'condition' => 'Brouillard',
                'icon' => 'Fog',
                'theme' => 'mist',
                'advice' => 'Gardez vos rappels visibles pour ne pas perdre votre elan.',
            ],
            51, 53, 55, 56, 57, 61, 63, 65, 66, 67, 80, 81, 82 => [
                'condition' => 'Pluie',
                'icon' => 'Rain',
                'theme' => 'rain',
                'advice' => 'Parfait pour valoriser vos routines a la maison et vos suivis rapides.',
            ],
            71, 73, 75, 77, 85, 86 => [
                'condition' => 'Neige',
                'icon' => 'Snow',
                'theme' => 'mist',
                'advice' => 'Conservez une routine douce et concentree sur l essentiel.',
            ],
            95, 96, 99 => [
                'condition' => 'Orage',
                'icon' => 'Storm',
                'theme' => 'storm',
                'advice' => 'Recentrez-vous sur une ou deux habitudes cle pour garder la constance.',
            ],
            default => [
                'condition' => 'Conditions variables',
                'icon' => 'Sky',
                'theme' => 'soft',
                'advice' => 'Adaptez vos habitudes du jour sans perdre votre continuité.',
            ],
        };
    }
}
