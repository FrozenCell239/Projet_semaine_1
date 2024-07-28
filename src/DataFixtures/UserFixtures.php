<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    public function __construct(private UserPasswordHasherInterface $passwordHasher){}

    public function load(ObjectManager $manager): void
    {
        # Demo fake user 1
        $user = new User();
        $user
            ->setUsername('the_smd')
            ->setEmail('smd@mail.com')
            ->setNickname('SuperMarioDuplantier')
            ->setVerified(true)
            ->setPassword($this->passwordHasher->hashPassword($user, 'Adminer1='));
        ;
        $manager->persist($user);

        # Demo fake user 2
        $user = new User();
        $user
            ->setUsername('jack_black_and_white')
            ->setEmail('jbw@mail.com')
            ->setNickname('Jack Barcode')
            ->setVerified(true)
            ->setPassword($this->passwordHasher->hashPassword($user, 'Adminer2='));
        ;
        $manager->persist($user);

        # Demo fake user 3
        $user = new User();
        $user
            ->setUsername('paulqqun')
            ->setEmail('paulqqun@mail.com')
            ->setNickname("Paul Quelqu'un")
            ->setVerified(true)
            ->setPassword($this->passwordHasher->hashPassword($user, 'Adminer3='));
        ;
        $manager->persist($user);

        # Saving the fake users
        $manager->flush();
    }
}