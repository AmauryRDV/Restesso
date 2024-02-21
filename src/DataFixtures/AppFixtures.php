<?php

namespace App\DataFixtures;

use App\Entity\Bean;
use App\Entity\Category;
use App\Entity\Coffee;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Faker\Generator;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{

    /**
     * @var Generator
     */
    private Generator $faker;

    /**
     * Password Hasher
     * @var UserPasswordHasherInterface $passwordEncoder
    */
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordEncoder) {
        $this->faker = Factory::create("fr_FR");
        $this->passwordHasher = $passwordEncoder;
    }

    public function load(ObjectManager $manager): void
    {
        // Public
        $publicUser = new User();
        $pwd = $this->faker->password(2, 6);
        $publicUser
            ->setUsername($this->faker->userName() . '@' . $pwd)
            ->setPassword($this->passwordHasher->hashPassword($publicUser, $pwd))
            ->setRoles(['ROLE_PUBLIC']);

        $manager->persist($publicUser);

        // User
        for ($i=0; $i < 10; $i++) {
            $userUser = new User();
            $pwd = $this->faker->password(2, 6);
            $userUser
                ->setUsername($this->faker->userName() . '@' . $pwd)
                ->setPassword($this->passwordHasher->hashPassword($userUser, $pwd))
                ->setRoles(['ROLE_USER']);
    
            $manager->persist($userUser);
        }

        $adminUser = new User();
        $adminUser
            ->setUsername('admin')
            ->setPassword($this->passwordHasher->hashPassword($adminUser, 'password'))
            ->setRoles(['ROLE_ADMIN']);

        $manager->persist($adminUser);
        $manager->flush();
    }
}
