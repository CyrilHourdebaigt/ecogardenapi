<?php

namespace App\Controller;

use App\Entity\Conseil;
use App\Repository\ConseilRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class ConseilController extends AbstractController
{
    // Route permettant de récuperer tous les conseils
    #[Route('/conseil', name: 'app_conseil_list', methods: ['GET'])]
    public function index(ConseilRepository $conseilRepository, SerializerInterface $serializer): JsonResponse
    {
        // On récupère tous les conseils présents dans la base de données
        $conseils = $conseilRepository->findAll();

        // On sérialise les conseils au format JSON
        $jsonConseils = $serializer->serialize($conseils, 'json');

        // On retourne la réponse JSON avec un code HTTP 200
        return new JsonResponse($jsonConseils, Response::HTTP_OK, [], true);
    }

    // Route permettant de récuperer conseils par mois
    #[Route('/conseil/{mois}', name: 'app_conseil_by_month', methods: ['GET'])]
    public function getByMonth(int $mois, ConseilRepository $conseilRepository, SerializerInterface $serializer): JsonResponse
    {
        // On vérifie que le mois demandé est valide
        if ($mois < 1 || $mois > 12) {
            return new JsonResponse(['error' => 'Le mois doit être compris entre 1 et 12.'], Response::HTTP_BAD_REQUEST);
        }

        // On récupère tous les conseils
        $conseils = $conseilRepository->findAll();

        // On prépare un tableau pour stocker uniquement les conseils du mois demandé
        $filteredConseils = [];

        foreach ($conseils as $conseil) {
            if (in_array($mois, $conseil->getMonths(), true)) {
                $filteredConseils[] = $conseil;
            }
        }

        // On sérialise les conseils filtrés au format JSON
        $jsonConseils = $serializer->serialize($filteredConseils, 'json');

        // On retourne la réponse JSON
        return new JsonResponse($jsonConseils, Response::HTTP_OK, [], true);
    }

    // Route permettant de créer un conseil
    #[Route('/conseil', name: 'app_conseil_create', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n’avez pas les droits suffisants pour créer un conseil.')]
    public function createConseil(Request $request, SerializerInterface $serializer, ValidatorInterface $validator, EntityManagerInterface $entityManager): JsonResponse
    {
        // On transforme le JSON reçu en tableau PHP pour vérifier qu’il est valide
        $data = json_decode($request->getContent(), true);

        if ($data === null) {
            return new JsonResponse(['error' => 'Le JSON envoyé est invalide.'], Response::HTTP_BAD_REQUEST);
        }

        // On désérialise le JSON reçu en objet Conseil
        /** @var Conseil $conseil */
        $conseil = $serializer->deserialize($request->getContent(), Conseil::class, 'json');

        // On valide l’entité Conseil grâce aux Assert définis dans l’entité
        $errors = $validator->validate($conseil);

        if ($errors->count() > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), Response::HTTP_BAD_REQUEST, [], true);
        }

        // On vérifie manuellement que chaque mois est bien un entier entre 1 et 12
        foreach ($conseil->getMonths() as $month) {
            if (!is_int($month) || $month < 1 || $month > 12) {
                return new JsonResponse(['error' => 'Chaque mois doit être un entier compris entre 1 et 12.'], Response::HTTP_BAD_REQUEST);
            }
        }

        // On enregistre le conseil en base
        $entityManager->persist($conseil);
        $entityManager->flush();

        // On retourne une réponse de succès avec le code 201 Created
        return new JsonResponse(
            [
                'message' => 'Conseil créé avec succès.',
                'conseil' => [
                    'id' => $conseil->getId(),
                    'content' => $conseil->getContent(),
                    'months' => $conseil->getMonths(),
                ],
            ],
            Response::HTTP_CREATED
        );
    }

    // Route permettant de mettre à jour un conseil
    #[Route('/conseil/{id}', name: 'app_conseil_update', methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n’avez pas les droits suffisants pour modifier un conseil.')]
    public function updateConseil(
        int $id,
        Request $request,
        ConseilRepository $conseilRepository,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        // On recherche le conseil à modifier en base de données
        $conseil = $conseilRepository->find($id);

        // Si le conseil n'existe pas, on retourne une erreur 404
        if (!$conseil) {
            return new JsonResponse(['error' => 'Conseil introuvable.'], Response::HTTP_NOT_FOUND);
        }

        // On transforme le JSON reçu en tableau PHP
        $data = json_decode($request->getContent(), true);

        // Si le JSON est invalide, on retourne une erreur 400
        if ($data === null) {
            return new JsonResponse(['error' => 'Le JSON envoyé est invalide.'], Response::HTTP_BAD_REQUEST);
        }

        // On met à jour uniquement les champs présents dans la requête
        if (array_key_exists('content', $data)) {
            $conseil->setContent($data['content']);
        }

        if (array_key_exists('months', $data)) {
            $conseil->setMonths($data['months']);
        }

        // On valide l'entité après modification
        $errors = $validator->validate($conseil);

        if ($errors->count() > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), Response::HTTP_BAD_REQUEST, [], true);
        }

        // Si le champ months est présent, on vérifie que chaque mois est valide
        foreach ($conseil->getMonths() as $month) {
            if (!is_int($month) || $month < 1 || $month > 12) {
                return new JsonResponse(['error' => 'Chaque mois doit être un entier compris entre 1 et 12.'], Response::HTTP_BAD_REQUEST);
            }
        }

        // On enregistre les modifications
        $entityManager->flush();

        // On retourne une réponse de succès avec le conseil mis à jour
        return new JsonResponse(
            [
                'message' => 'Conseil modifié avec succès.',
                'conseil' => [
                    'id' => $conseil->getId(),
                    'content' => $conseil->getContent(),
                    'months' => $conseil->getMonths(),
                ],
            ],
            Response::HTTP_OK
        );
    }

    // Route permettant de supprimer un conseil
    #[Route('/conseil/{id}', name: 'app_conseil_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n’avez pas les droits suffisants pour supprimer un conseil.')]
    public function deleteConseil(int $id, ConseilRepository $conseilRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        // On recherche le conseil à supprimer en base
        $conseil = $conseilRepository->find($id);

        // Si le conseil n'existe pas, erreur 404
        if (!$conseil) {
            return new JsonResponse(['error' => 'Conseil introuvable.'], Response::HTTP_NOT_FOUND);
        }

        // On supprime le conseil
        $entityManager->remove($conseil);
        $entityManager->flush();

        // On retourne une réponse JSON de succès
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
