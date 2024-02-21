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
     * @var UserPasswordHasherInterface
     */
    private UserPasswordHasherInterface $userPasswordHasher;
    public function __construct(UserPasswordHasherInterface $userPasswordHasher) {
        $this->faker = Factory::create("fr_FR");
        $this->userPasswordHasher = $userPasswordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        //Public
        $publicUser = new User();
        $password = $this->faker->password(2,6);
        $publicUser
            ->setUuid($this->faker->uuid() . "@" . $password)
            ->setPassword($this->userPasswordHasher->hashPassword($publicUser, $password))
            ->setRoles(["ROLE_PUBLIC"]);
        $manager ->persist($publicUser);
            
        for ($i=0;$i<10;$i++)
        {
            $userUser = new User();
            $password = $this->faker->password(2,6);
            $userUser
            ->setUuid($this->faker->uuid() . "@" . $password)
            ->setPassword($this->userPasswordHasher->hashPassword($userUser,$password))
            ->setRoles(["ROLE_PUBLIC"]);
        $manager ->persist($userUser);
        }

        $adminUser = new User();
        $adminUser
        ->setUuid("admin")
        ->setPassword($this->userPasswordHasher->hashPassword($adminUser,"admin"))
        ->setRoles(["ROLE_ADMIN"]);
        $manager->persist($adminUser);

        

        for ($j=0; $j < 20; $j++) {
            $category = new Category();
            $category->setName("category ". $j);
            $createdAt = $this->faker->dateTimeBetween("-1 week","now");
            $updatedAt = $this->faker->dateTimeBetween($createdAt,"now");
            $category->setCreatedAt($createdAt);
            $category->setUpdatedAt($updatedAt);
            $category->setStatus("on");
            $manager->persist($category);
            $bean = new Bean();
            $bean->setName("Bean ". $j);
            $bean->setOrigin("Pays ". $j);
            $bean->setDescription("Lorem ipsum dolor sit amet, consectetur adipiscing elit. Maecenas in.". $j);
            $manager->persist($bean);

            for ($i = 0; $i < 5; $i++) {
                $ig = new Coffee();
                $ig->setName("coffee ". $i+$j*5);
                $ig->setDescription("desc ". $i+$j*5);
                $createdAt = $this->faker->dateTimeBetween("-1 week","now");
                $updatedAt = $this->faker->dateTimeBetween($createdAt,"now");
                $ig->setCreatedAt($createdAt);
                $ig->setUpdatedAt($updatedAt);
                $ig->setStatus("on");
                $ig->setCategory($category);
                $ig->setBean($bean);
                $manager->persist($ig);
            }
        }

        $manager->flush();
    }
}
