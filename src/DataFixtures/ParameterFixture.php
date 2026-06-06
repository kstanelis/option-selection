<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Parameter;
use App\Entity\ParameterCombination;
use App\Entity\ParameterOption;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ParameterFixture extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $param1 = new Parameter('parameter1');
        $optionA = new ParameterOption($param1, 'A');
        $optionB = new ParameterOption($param1, 'B');
        $optionC = new ParameterOption($param1, 'C');
        $param1->addOption($optionA);
        $param1->addOption($optionB);
        $param1->addOption($optionC);

        $param2 = new Parameter('parameter2');
        $optionX = new ParameterOption($param2, 'X');
        $optionY = new ParameterOption($param2, 'Y');
        $optionZ = new ParameterOption($param2, 'Z');
        $param2->addOption($optionX);
        $param2->addOption($optionY);
        $param2->addOption($optionZ);

        $manager->persist($param1);
        $manager->persist($param2);
        $manager->flush();

        $validCombinations = [
            [$optionA, $optionX],
            [$optionA, $optionZ],
            [$optionB, $optionX],
            [$optionB, $optionY],
            [$optionB, $optionZ],
            [$optionC, $optionX],
            [$optionC, $optionY],
        ];

        foreach ($validCombinations as [$opt1, $opt2]) {
            $manager->persist(new ParameterCombination($opt1, $opt2));
        }

        $manager->flush();
    }
}
