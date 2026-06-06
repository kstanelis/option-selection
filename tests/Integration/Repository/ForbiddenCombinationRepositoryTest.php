<?php

declare(strict_types=1);

namespace App\Tests\Integration\Repository;

use App\Entity\ForbiddenCombination;
use App\Entity\Parameter;
use App\Entity\ParameterOption;
use App\Repository\ForbiddenCombinationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ForbiddenCombinationRepositoryTest extends KernelTestCase
{
    private ForbiddenCombinationRepository $repository;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = self::getContainer()->get('doctrine')->getManager();
        $this->repository = self::getContainer()->get(ForbiddenCombinationRepository::class);
        $this->em->getConnection()->executeQuery('DELETE FROM forbidden_combination');
        $this->em->getConnection()->executeQuery('DELETE FROM parameter_option');
        $this->em->getConnection()->executeQuery('DELETE FROM parameter');
    }

    public function testIsForbiddenReturnsFalseWhenNotForbidden(): void
    {
        $param1 = new Parameter('param1');
        $param2 = new Parameter('param2');
        $opt1 = new ParameterOption($param1, 'A', 0);
        $opt2 = new ParameterOption($param2, 'X', 0);
        $param1->addOption($opt1);
        $param2->addOption($opt2);
        $this->em->persist($param1);
        $this->em->persist($param2);
        $this->em->flush();

        $result = $this->repository->isForbidden($opt1, $opt2);
        $this->assertFalse($result);
    }

    public function testIsForbiddenReturnsTrueWhenForbidden(): void
    {
        $em = self::getContainer()->get('doctrine')->getManager();
        $param1 = new Parameter('param1');
        $param2 = new Parameter('param2');
        $opt1 = new ParameterOption($param1, 'A', 0);
        $opt2 = new ParameterOption($param2, 'Y', 0);
        $param1->addOption($opt1);
        $param2->addOption($opt2);
        $forbidden = new ForbiddenCombination($opt1, $opt2);
        $em->persist($param1);
        $em->persist($param2);
        $em->persist($forbidden);
        $em->flush();

        $result = $this->repository->isForbidden($opt1, $opt2);
        $this->assertTrue($result);
    }

    public function testFindByOptionAReturnsEmptyWhenNoneFound(): void
    {
        $em = self::getContainer()->get('doctrine')->getManager();
        $param1 = new Parameter('param1');
        $opt1 = new ParameterOption($param1, 'A', 0);
        $param1->addOption($opt1);
        $em->persist($param1);
        $em->flush();

        $result = $this->repository->findByOptionA($opt1);
        $this->assertEmpty($result);
    }

    public function testFindByOptionAReturnsCombinations(): void
    {
        $em = self::getContainer()->get('doctrine')->getManager();
        $param1 = new Parameter('param1');
        $param2 = new Parameter('param2');
        $optA = new ParameterOption($param1, 'A', 0);
        $optX = new ParameterOption($param2, 'X', 0);
        $optY = new ParameterOption($param2, 'Y', 1);
        $param1->addOption($optA);
        $param2->addOption($optX);
        $param2->addOption($optY);
        $forbidden1 = new ForbiddenCombination($optA, $optX);
        $forbidden2 = new ForbiddenCombination($optA, $optY);
        $em->persist($param1);
        $em->persist($param2);
        $em->persist($forbidden1);
        $em->persist($forbidden2);
        $em->flush();

        $result = $this->repository->findByOptionA($optA);
        $this->assertCount(2, $result);
    }

    public function testFindByOptionBReturnsEmptyWhenNoneFound(): void
    {
        $em = self::getContainer()->get('doctrine')->getManager();
        $param2 = new Parameter('param2');
        $opt2 = new ParameterOption($param2, 'Y', 0);
        $param2->addOption($opt2);
        $em->persist($param2);
        $em->flush();

        $result = $this->repository->findByOptionB($opt2);
        $this->assertEmpty($result);
    }

    public function testFindByOptionBReturnsCombinations(): void
    {
        $em = self::getContainer()->get('doctrine')->getManager();
        $param1 = new Parameter('param1');
        $param2 = new Parameter('param2');
        $optA = new ParameterOption($param1, 'A', 0);
        $optB = new ParameterOption($param1, 'B', 1);
        $optY = new ParameterOption($param2, 'Y', 0);
        $param1->addOption($optA);
        $param1->addOption($optB);
        $param2->addOption($optY);
        $forbidden1 = new ForbiddenCombination($optA, $optY);
        $forbidden2 = new ForbiddenCombination($optB, $optY);
        $em->persist($param1);
        $em->persist($param2);
        $em->persist($forbidden1);
        $em->persist($forbidden2);
        $em->flush();

        $result = $this->repository->findByOptionB($optY);
        $this->assertCount(2, $result);
    }
}
