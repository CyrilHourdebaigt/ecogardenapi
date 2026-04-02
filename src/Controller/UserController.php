<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class UserController extends AbstractController
{
    // Route publique permettant de créer un nouvel utilisateur
    #[Route('/user', name: 'app_user_create', methods: ['POST'])]
    public function createUser(
        Request $request,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        UserRepository $userRepository,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        // On récupère et transforme le contenu JSON envoyé dans la requête en tableau PHP
        $data = json_decode($request->getContent(), true);

        // Si le JSON est invalide, erreur 400 Bad Request
        if ($data === null) {
            return new JsonResponse(['error' => 'Le JSON envoyé est invalide.'], Response::HTTP_BAD_REQUEST);
        }

        // On vérifie manuellement que le MDP a bien été envoyé
        if (empty($data['password'])) {
            return new JsonResponse(['error' => 'Le mot de passe est obligatoire.'], Response::HTTP_BAD_REQUEST);
        }

        // On vérifie si un utilisateur existe déjà
        $existingUser = $userRepository->findOneBy([
            'login' => $data['login'] ?? null,
        ]);

        if ($existingUser) {
            return new JsonResponse(['error' => 'Ce login est déjà utilisé.'], Response::HTTP_BAD_REQUEST);
        }

        // On désérialise le JSON reçu pour créer un objet User à partir des données envoyées
        /** @var User $user */
        $user = $serializer->deserialize($request->getContent(), User::class, 'json');

        // On attribue par défaut le rôle ROLE_USER
        $user->setRoles(['ROLE_USER']);

        // On valide l'objet User en appliquant les contraintes
        $errors = $validator->validate($user);

        // Si des erreurs de validation existent,
        // on les sérialise en JSON et on les retourne avec un code 400
        if ($errors->count() > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), Response::HTTP_BAD_REQUEST, [], true);
        }

        // On hash le mot de passe avant de l'enregistrer en BDD
        $hashedPassword = $passwordHasher->hashPassword($user, $data['password']);
        $user->setPassword($hashedPassword);

        $entityManager->persist($user);
        $entityManager->flush();

        // On retourne une réponse JSON de succès avec le code HTTP 201 Created
        return new JsonResponse(
            [
                'message' => 'Utilisateur créé avec succès.',
                'user' => [
                    'id' => $user->getId(),
                    'login' => $user->getLogin(),
                    'city' => $user->getCity(),
                    'roles' => $user->getRoles(),
                ],
            ],
            Response::HTTP_CREATED
        );
    }
}
