<?php

namespace App\DataFixtures;

use App\Entity\Car;
use App\Entity\CarCategory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // $product = new Product();
        // $manager->persist($product);
        $car = array();
        $carCategory = array(); 
        $faker = Faker\Factory::create();
        for($i = 0; $i < 10; ++$i){
            $carCategory[$i] = new CarCategory();
            $carCategory[$i]->setName($faker->word());
            $manager->persist($carCategory[$i]);
        }

        for($i = 0; $i < 1000; ++$i){
            $car[$i] = new Car();
            $car[$i]->setNbDoors($faker->randomDigit());
            $car[$i]->setNbSeats($faker->randomDigit());
            $car[$i]->setName($faker->word());
            $car[$i]->setCost($faker->randomFloat(2));
            $category = $i % 10;
            $car[$i]->setCategory($carCategory[$category]);
            $manager->persist($car[$i]);
        }
        $manager->flush();
    }
}
