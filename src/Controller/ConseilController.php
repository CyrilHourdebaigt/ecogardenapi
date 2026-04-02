<?php

namespace App\Controller;

use App\Repository\ConseilRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class ConseilController extends AbstractController
{
    #[Route('/conseil', name: 'app_conseil_list', methods: ['GET'])]
    public function index(ConseilRepository $conseilRepository): JsonResponse
    {
        // On récupère tous les conseils présents dans la base de données
        $conseils = $conseilRepository->findAll();

        // On prépare un tableau simple pour construire la réponse JSON
        $result = [];

        foreach ($conseils as $conseil) {
            $result[] = [
                'id' => $conseil->getId(),
                'content' => $conseil->getContent(),
                'months' => $conseil->getMonths(),
            ];
        }

        // On retourne les données au format JSON
        return $this->json($result);
    }

    #[Route('/conseil/{mois}', name: 'app_conseil_by_month', methods: ['GET'])]
    public function getByMonth(int $mois, ConseilRepository $conseilRepository): JsonResponse
    {
        // On vérifie que le mois est bien compris entre 1 et 12
        if ($mois < 1 || $mois > 12) {
            return $this->json(
                ['error' => 'Le mois doit être compris entre 1 et 12.'],
                JsonResponse::HTTP_BAD_REQUEST
            );
        }

        // On récupère tous les conseils
        $conseils = $conseilRepository->findAll();

        // On prépare un tableau pour stocker les conseils correspondant au mois demandé
        $result = [];

        foreach ($conseils as $conseil) {
            if (in_array($mois, $conseil->getMonths(), true)) {
                $result[] = [
                    'id' => $conseil->getId(),
                    'content' => $conseil->getContent(),
                    'months' => $conseil->getMonths(),
                ];
            }
        }

        // On retourne les conseils trouvés au format JSON
        return $this->json($result);
    }
}
