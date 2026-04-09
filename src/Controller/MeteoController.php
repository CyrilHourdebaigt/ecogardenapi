<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

final class MeteoController extends AbstractController
{
    // Route permettant de récuperer la météo associé à la ville d'un utilisateur
    #[Route('/meteo', name: 'app_meteo_user_city', methods: ['GET'])]
    public function getByUserCity(HttpClientInterface $httpClient, TagAwareCacheInterface $cachePool): JsonResponse
    {
        // On récupère l'utilisateur connecté grâce au token JWT
        $user = $this->getUser();

        // Si aucun utilisateur n'est connecté, on retourne une erreur 401
        if (!$user instanceof User) {
            return new JsonResponse(
                ['error' => 'Utilisateur non authentifié.'],
                Response::HTTP_UNAUTHORIZED
            );
        }

        // On récupère la ville associée au compte utilisateur
        $ville = $user->getCity();

        // Si aucune ville n'est renseignée, on retourne une erreur 400
        if (!$ville) {
            return new JsonResponse(
                ['error' => 'Aucune ville n’est renseignée pour cet utilisateur.'],
                Response::HTTP_BAD_REQUEST
            );
        }

        // On récupère la clé API depuis le fichier .env.local
        $apiKey = $_ENV['OPENWEATHER_API_KEY'] ?? null;

        // Si la clé n'existe pas, on retourne une erreur 500
        if (!$apiKey) {
            throw new HttpException(
                Response::HTTP_INTERNAL_SERVER_ERROR,
                'La clé API OpenWeatherMap est introuvable.'
            );
        }

        $idCache = 'meteo-' . mb_strtolower(trim($ville));

        $weatherResult = $cachePool->get($idCache, function (ItemInterface $item) use ($ville, $httpClient, $apiKey) {
            // On ajoute un tag pour pouvoir invalider facilement le cache météo si besoin
            $item->tag('meteoCache');

            // On garde le cache 60 secondes
            $item->expiresAfter(60);

            // 1. Appel à l'API de géocodage
            $geoResponse = $httpClient->request('GET', 'https://api.openweathermap.org/geo/1.0/direct', [
                'query' => [
                    'q' => $ville,
                    'limit' => 1,
                    'appid' => $apiKey,
                ],
            ]);

            $geoData = $geoResponse->toArray();

            // Si aucune ville n'est trouvée, on retourne une erreur 404
            if (empty($geoData)) {
                throw new HttpException(
                    Response::HTTP_NOT_FOUND,
                    'Ville introuvable.'
                );
            }

            // On prend la première proposition trouvée
            $firstResult = $geoData[0];

            $latitude = $firstResult['lat'];
            $longitude = $firstResult['lon'];

            // 2. Appel à l'API météo
            $weatherResponse = $httpClient->request('GET', 'https://api.openweathermap.org/data/2.5/weather', [
                'query' => [
                    'lat' => $latitude,
                    'lon' => $longitude,
                    'appid' => $apiKey,
                    'units' => 'metric',
                    'lang' => 'fr',
                ],
            ]);

            $weatherData = $weatherResponse->toArray();

            // Si la météo n'est pas trouvée, on retourne une erreur 404
            if (empty($weatherData)) {
                throw new HttpException(
                    Response::HTTP_NOT_FOUND,
                    'Météo introuvable pour cette ville.'
                );
            }

            // On retourne uniquement les informations essentielles
            return [
                'city' => $firstResult['name'] ?? $ville,
                'country' => $firstResult['country'] ?? null,
                'description' => $weatherData['weather'][0]['description'] ?? null,
                'temperature' => $weatherData['main']['temp'] ?? null,
            ];
        });

        return new JsonResponse($weatherResult, Response::HTTP_OK);
    }

    // Route permettant de récuperer la météo d'une ville
    #[Route('/meteo/{ville}', name: 'app_meteo_by_city', methods: ['GET'])]
    public function getByCity(string $ville, HttpClientInterface $httpClient, TagAwareCacheInterface $cachePool): JsonResponse
    {
        // On récupère la clé API depuis le fichier .env.local
        $apiKey = $_ENV['OPENWEATHER_API_KEY'] ?? null;

        // Si la clé n'existe pas, on retourne une erreur 500
        if (!$apiKey) {
            throw new HttpException(
                Response::HTTP_INTERNAL_SERVER_ERROR,
                'La clé API OpenWeatherMap est introuvable.'
            );
        }

        $idCache = 'meteo-' . mb_strtolower(trim($ville));

        $weatherResult = $cachePool->get($idCache, function (ItemInterface $item) use ($ville, $httpClient, $apiKey) {
            //echo "L'ELEMENT N'EST PAS ENCORE EN CACHE !\n";

            // On ajoute un tag pour pouvoir invalider facilement le cache météo si besoin
            $item->tag('meteoCache');

            // On garde le cache 60 secondes
            $item->expiresAfter(60);

            // 1. Appel à l'API de géocodage
            $geoResponse = $httpClient->request('GET', 'https://api.openweathermap.org/geo/1.0/direct', [
                'query' => [
                    'q' => $ville,
                    'limit' => 1,
                    'appid' => $apiKey,
                ],
            ]);

            $geoData = $geoResponse->toArray();

            // Si aucune ville n'est trouvée, on retourne une erreur 404
            if (empty($geoData)) {
                throw new HttpException(
                    Response::HTTP_NOT_FOUND,
                    'Ville introuvable.'
                );
            }

            // On prend la première proposition trouvée
            $firstResult = $geoData[0];

            $latitude = $firstResult['lat'];
            $longitude = $firstResult['lon'];

            // 2. Appel à l'API météo
            $weatherResponse = $httpClient->request('GET', 'https://api.openweathermap.org/data/2.5/weather', [
                'query' => [
                    'lat' => $latitude,
                    'lon' => $longitude,
                    'appid' => $apiKey,
                    'units' => 'metric',
                    'lang' => 'fr',
                ],
            ]);

            $weatherData = $weatherResponse->toArray();

            // Si la météo n'est pas trouvée, on retourne une erreur 404
            if (empty($weatherData)) {
                throw new HttpException(
                    Response::HTTP_NOT_FOUND,
                    'Météo introuvable pour cette ville.'
                );
            }

            // On retourne uniquement les informations essentielles
            return [
                'city' => $firstResult['name'] ?? $ville,
                'country' => $firstResult['country'] ?? null,
                'description' => $weatherData['weather'][0]['description'] ?? null,
                'temperature' => $weatherData['main']['temp'] ?? null,
            ];
        });

        return new JsonResponse($weatherResult, Response::HTTP_OK);
    }
}
