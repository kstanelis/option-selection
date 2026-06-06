<?php

declare(strict_types=1);

namespace App\Tests\Integration\Repository;

use App\Repository\ParameterCombinationRepository;
use App\Tests\Support\SeedsParameterData;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ParameterCombinationRepositoryTest extends KernelTestCase
{
    use SeedsParameterData;

    private ParameterCombinationRepository $repository;
    private EntityManager $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = self::getContainer()->get('doctrine.orm.entity_manager');
        $this->repository = self::getContainer()->get(ParameterCombinationRepository::class);
        $this->clearParameterData($this->entityManager);
        $this->seedParameterData($this->entityManager);
    }

    public function testFindBySelectionEmpty(): void
    {
        $results = $this->repository->findBySelection([]);
        $this->assertCount(7, $results);
    }

    public function testFindBySelectionParameter1A(): void
    {
        $results = $this->repository->findBySelection([0 => 'A']);
        $this->assertCount(2, $results);
    }

    public function testFindBySelectionParameter1B(): void
    {
        $results = $this->repository->findBySelection([0 => 'B']);
        $this->assertCount(3, $results);
    }

    public function testFindBySelectionParameter2Z(): void
    {
        $results = $this->repository->findBySelection([1 => 'Z']);
        $this->assertCount(2, $results);
    }

    public function testFindBySelectionBothParameters(): void
    {
        $results = $this->repository->findBySelection([0 => 'B', 1 => 'Y']);
        $this->assertCount(1, $results);
    }

    public function testGetDistinctValuesForParameter1(): void
    {
        $combinations = $this->repository->findAll();
        $values = $this->repository->getDistinctValues($combinations, 0);
        $this->assertCount(3, $values);
        $this->assertContains('A', $values);
        $this->assertContains('B', $values);
        $this->assertContains('C', $values);
    }

    public function testGetDistinctValuesForParameter2(): void
    {
        $combinations = $this->repository->findAll();
        $values = $this->repository->getDistinctValues($combinations, 1);
        $this->assertCount(3, $values);
        $this->assertContains('X', $values);
        $this->assertContains('Y', $values);
        $this->assertContains('Z', $values);
    }
}
