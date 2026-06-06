<?php

declare(strict_types=1);

namespace App\Tests\Integration\Repository;

use App\Entity\Parameter;
use App\Entity\ParameterOption;
use App\Repository\ParameterRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ParameterRepositoryTest extends KernelTestCase
{
    private ParameterRepository $repository;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = self::getContainer()->get('doctrine')->getManager();
        $this->repository = self::getContainer()->get(ParameterRepository::class);
        $this->em->getConnection()->executeQuery('DELETE FROM parameter_option');
        $this->em->getConnection()->executeQuery('DELETE FROM parameter');
    }

    public function testFindByNameReturnsNullWhenNotFound(): void
    {
        $result = $this->repository->findByName('nonexistent');
        $this->assertNull($result);
    }

    public function testFindByNameReturnsParameterWhenFound(): void
    {
        $parameter = new Parameter('test_param');
        $this->em->persist($parameter);
        $this->em->flush();

        $result = $this->repository->findByName('test_param');
        $this->assertNotNull($result);
        $this->assertEquals('test_param', $result->getName());
    }

    public function testFindAllReturnsEmptyArrayWhenNoParameters(): void
    {
        $result = $this->repository->findAll();
        $this->assertIsArray($result);
    }

    public function testFindAllReturnsSortedByName(): void
    {
        $param1 = new Parameter('zebra');
        $param2 = new Parameter('apple');
        $param3 = new Parameter('banana');
        $this->em->persist($param1);
        $this->em->persist($param2);
        $this->em->persist($param3);
        $this->em->flush();

        $result = $this->repository->findAll();
        $names = array_map(fn (Parameter $p) => $p->getName(), $result);
        $this->assertEquals(['apple', 'banana', 'zebra'], $names);
    }
}
