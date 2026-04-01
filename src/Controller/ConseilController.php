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
}
