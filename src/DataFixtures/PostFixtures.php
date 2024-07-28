<?php

namespace App\DataFixtures;

use App\Entity\Post;
use App\Repository\UserRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\String\Slugger\SluggerInterface;
use Faker;

class PostFixtures extends Fixture implements DependentFixtureInterface
{
    public function __construct(
        private SluggerInterface $slugger,
        private UserRepository $userRepository
    ){}

    public function getDependencies() : array
    {
        return [UserFixtures::class];
    }

    public function load(ObjectManager $manager): void
    {
        # Demo fake posts
        $available_fake_users = ['the_smd', 'jack_black_and_white', 'paulqqun'];
        $faker = Faker\Factory::create('fr_FR');
        for($i = 0; $i < 9; $i++){
            $post = new Post();
            $post
                ->setTitle($faker->text(50))
                ->setSummary($faker->text(100))
                ->setContent($faker->text(1000))
                ->setSlug($this->slugger->slug($post->getTitle())->lower())
                ->setAuthor($this->userRepository->findOneBy([
                    'username' => $available_fake_users[array_rand($available_fake_users)]
                ]))
            ;
            $manager->persist($post);
        };

        # Saving the fake posts
        $manager->flush();
    }
}