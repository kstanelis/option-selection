<?php

declare(strict_types=1);

namespace App\Tests\Integration\Repository;

use App\Repository\ParameterRepository;
use App\Tests\Support\SeedsParameterData;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ParameterRepositoryTest extends KernelTestCase
{
    use SeedsParameterData;

    private ParameterRepository $repository;
    private EntityManager $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = self::getContainer()->get('doctrine.orm.entity_manager');
        $this->repository = self::getContainer()->get(ParameterRepository::class);
        $this->clearParameterData($this->entityManager);
        $this->seedParameterData($this->entityManager);
    }

    public function testFindByNameExists(): void
    {
        $result = $this->repository->findByName('parameter1');
        $this->assertNotNull($result);
        $this->assertSame('parameter1', $result->getName());
    }

    public function testFindByNameNotExists(): void
    {
        $result = $this->repository->findByName('nonexistent');
        $this->assertNull($result);
    }

    public function testFindByNameReturnsCorrectParameter(): void
    {
        $result = $this->repository->findByName('parameter2');
        $this->assertNotNull($result);
        $this->assertSame('parameter2', $result->getName());
    }
}
