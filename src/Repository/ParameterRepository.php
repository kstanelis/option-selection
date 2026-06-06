<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Parameter;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Parameter>
 */
class ParameterRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Parameter::class);
    }

    /**
     * Find parameter by name.
     */
    public function findByName(string $name): ?Parameter
    {
        return $this->findOneBy(['name' => $name]);
    }
}
