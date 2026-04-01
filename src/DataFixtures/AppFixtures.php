<?php

namespace App\DataFixtures;

use App\Entity\Conseil;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        // Création de l'admin
        $admin = new User();
        $admin->setLogin('admin');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setCity('Pau');

        $hashedAdminPassword = $this->passwordHasher->hashPassword($admin, 'admin123');
        $admin->setPassword($hashedAdminPassword);

        $manager->persist($admin);

        // Création d'un utilisateur
        $user = new User();
        $user->setLogin('cyril');
        $user->setRoles(['ROLE_USER']);
        $user->setCity('Pau');

        $hashedUserPassword = $this->passwordHasher->hashPassword($user, 'password123');
        $user->setPassword($hashedUserPassword);

        $manager->persist($user);

        // Création des conseils
        $conseil1 = new Conseil();
        $conseil1->setContent('Pensez à protéger les semis du gel.');
        $conseil1->setMonths([1, 2, 12]);
        $manager->persist($conseil1);

        $conseil2 = new Conseil();
        $conseil2->setContent('Taillez les arbustes après la floraison.');
        $conseil2->setMonths([3, 4, 5]);
        $manager->persist($conseil2);

        $conseil3 = new Conseil();
        $conseil3->setContent('Arrosez tôt le matin pour limiter l’évaporation.');
        $conseil3->setMonths([6, 7, 8]);
        $manager->persist($conseil3);

        $conseil4 = new Conseil();
        $conseil4->setContent('Préparez le sol avant les plantations d’automne.');
        $conseil4->setMonths([9, 10, 11]);
        $manager->persist($conseil4);

        $manager->flush();
    }
}
