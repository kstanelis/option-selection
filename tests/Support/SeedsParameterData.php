<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Entity\Parameter;
use App\Entity\ParameterCombination;
use App\Entity\ParameterOption;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Seeds the canonical Variant B graph used across tests: two parameters,
 * their options (A/B/C and X/Y/Z), and the 7 valid combinations
 * (all 9 pairs minus the forbidden (A,Y) and (C,Z)).
 */
trait SeedsParameterData
{
    private function clearParameterData(EntityManagerInterface $em): void
    {
        $em->createQuery('DELETE FROM App\Entity\ParameterCombination')->execute();
        $em->createQuery('DELETE FROM App\Entity\ParameterOption')->execute();
        $em->createQuery('DELETE FROM App\Entity\Parameter')->execute();
    }

    private function seedParameterData(EntityManagerInterface $em): void
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

        $em->persist($param1);
        $em->persist($param2);
        $em->flush();

        $combinations = [
            new ParameterCombination($optionA, $optionX),
            new ParameterCombination($optionA, $optionZ),
            new ParameterCombination($optionB, $optionX),
            new ParameterCombination($optionB, $optionY),
            new ParameterCombination($optionB, $optionZ),
            new ParameterCombination($optionC, $optionX),
            new ParameterCombination($optionC, $optionY),
        ];

        foreach ($combinations as $combination) {
            $em->persist($combination);
        }
        $em->flush();
    }
}
