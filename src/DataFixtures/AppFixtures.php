<?php

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\Coffee;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Faker\Generator;

class AppFixtures extends Fixture
{

    /**
     * @var Generator
     */
    private Generator $faker;

    public function __construct() {
        $this->faker = Factory::create("fr_FR");
    }

    public function load(ObjectManager $manager): void
    {
        for ($j=0; $j < 20; $j++) {
            $category = new Category();
            $category->setName("category ". $j);
            $createdAt = $this->faker->dateTimeBetween("-1 week","now");
            $updatedAt = $this->faker->dateTimeBetween($createdAt,"now");
            $category->setCreatedAt();
            $category->setUpdatedAt();
            $category->setStatus("on");
            $manager->persist($category);

            for ($i = 0; $i < 5; $i++) {
                $ig = new Coffee();
                $ig->setName("coffee ". $i+$j*5);
                $ig->setDescription("desc ". $i+$j*5);
                $createdAt = $this->faker->dateTimeBetween("-1 week","now");
                $updatedAt = $this->faker->dateTimeBetween($createdAt,"now");
                $ig->setCreatedAt();
                $ig->setUpdatedAt();
                $ig->setStatus("on");
                $ig->setCategory($category);
                $manager->persist($ig);
            }
        }

        $manager->flush();
    }
}
