<?php

namespace App\DataFixtures;

use App\Entity\Bean;
use App\Entity\Category;
use App\Entity\Coffee;
use App\Entity\Taste;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Faker\Generator;
use Psr\Log\LoggerInterface;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{

    /**
     * @var Generator
     */
    private Generator $faker;

    /**
     * Password Hasher
     * @var UserPasswordHasherInterface
     */
    private UserPasswordHasherInterface $userPasswordHasher;

    /**
     * Dotenv
     * @var Dotenv
     */
    private Dotenv $dotenv;

    private LoggerInterface $logger;

    public function __construct(UserPasswordHasherInterface $userPasswordHasher, LoggerInterface $loggerInterface) {
        $this->faker = Factory::create("fr_FR");
        $this->userPasswordHasher = $userPasswordHasher;
        $this->dotenv = new Dotenv();
        $this->logger = $loggerInterface;
    }

    public function load(ObjectManager $manager): void
    {
        // Public
        $publicUsername = $_ENV['PUBLIC_USER_ID'];
        $publicPassword = $_ENV['PUBLIC_USER_PASSWORD'];

        $publicUser = new User();
        $publicUser
            ->setUuid($publicUsername . "@" . $publicPassword)
            ->setPassword($this->userPasswordHasher->hashPassword($publicUser, $publicPassword))
            ->setRoles(["ROLE_PUBLIC"]);
        $manager ->persist($publicUser);
            
        // Users
        for ($i=0; $i<10; $i++)
        {
            $userUser = new User();
            $password = $this->faker->password(2,6);
            $userUser
                ->setUuid($this->faker->uuid() . "@" . $password)
                ->setPassword($this->userPasswordHasher->hashPassword($userUser, $password))
                ->setRoles(["ROLE_PUBLIC"]);
            $manager ->persist($userUser);
        }

        // Admin
        $adminUsername = $_ENV['ADMIN_USER_ID'];
        $adminPassword = $_ENV['ADMIN_USER_PASSWORD'];

        $adminUser = new User();
        $adminUser
            ->setUuid($adminUsername)
            ->setPassword($this->userPasswordHasher->hashPassword($adminUser, $adminPassword))
            ->setRoles(["ROLE_ADMIN"]);
        $manager->persist($adminUser);

        // Category, Bean and Taste
        for ($j=0; $j < 20; $j++) {
            $category = new Category();
            $category->setName("category ". $j);
            $createdAt = $this->faker->dateTimeBetween("-1 week","now");
            $updatedAt = $this->faker->dateTimeBetween($createdAt,"now");
            $category->setCreatedAt($createdAt);
            $category->setUpdatedAt($updatedAt);
            $category->setStatus("active");
            $manager->persist($category);

            $bean = new Bean();
            $bean->setName("Bean ". $j);
            $bean->setOrigin("Pays ". $j);
            $bean->setCreatedAt($createdAt);
            $bean->setUpdatedAt($updatedAt);
            $bean->setStatus("active");
            $bean->setDescription("Lorem ipsum dolor sit amet, consectetur adipiscing elit. Maecenas in.". $j);
            $manager->persist($bean);

            $taste = new Taste();
            $taste->setName("Taste ". $j);
            $taste->setDescription("desc ". $j);
            $taste->setCaffeineRate($this->faker->randomFloat(2,0,1));
            $taste->setIntensity($this->faker->numberBetween(0, 5));
            $taste->setCreatedAt($createdAt);
            $taste->setUpdatedAt($updatedAt);
            $taste->setStatus("active");
            $taste->setDescription("Lorem ipsum dolor sit amet, consectetur adipiscing elit. Maecenas in.". $j);
            $manager->persist($taste);

            // Coffee
            for ($i = 0; $i < 5; $i++) {
                $ig = new Coffee();
                $ig->setName("coffee ". $i + $j * 5);
                $ig->setDescription("desc ". $i + $j * 5);
                $createdAt = $this->faker->dateTimeBetween("-1 week","now");
                $updatedAt = $this->faker->dateTimeBetween($createdAt,"now");
                $ig->setCreatedAt($createdAt);
                $ig->setUpdatedAt($updatedAt);
                $ig->setStatus("active");
                $ig->setCategory($category);
                $ig->setBean($bean);
                $manager->persist($ig);
            }
        }

        $manager->flush();
    }
}
