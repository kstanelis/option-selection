<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\ForbiddenCombination;
use App\Entity\Parameter;
use App\Entity\ParameterOption;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class PdfDatasetFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $parameter1 = new Parameter('parameter1');
        $optionA = new ParameterOption($parameter1, 'A', 0);
        $optionB = new ParameterOption($parameter1, 'B', 1);
        $optionC = new ParameterOption($parameter1, 'C', 2);
        $parameter1->addOption($optionA);
        $parameter1->addOption($optionB);
        $parameter1->addOption($optionC);

        $parameter2 = new Parameter('parameter2');
        $optionX = new ParameterOption($parameter2, 'X', 0);
        $optionY = new ParameterOption($parameter2, 'Y', 1);
        $optionZ = new ParameterOption($parameter2, 'Z', 2);
        $parameter2->addOption($optionX);
        $parameter2->addOption($optionY);
        $parameter2->addOption($optionZ);

        $manager->persist($parameter1);
        $manager->persist($parameter2);
        $manager->flush();

        $manager->persist(new ForbiddenCombination($optionA, $optionY));
        $manager->persist(new ForbiddenCombination($optionC, $optionZ));
        $manager->flush();
    }
}
