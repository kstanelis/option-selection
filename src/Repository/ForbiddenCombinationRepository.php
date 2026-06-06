<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ForbiddenCombination;
use App\Entity\ParameterOption;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<ForbiddenCombination> */
class ForbiddenCombinationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ForbiddenCombination::class);
    }

    /** @return ForbiddenCombination[] */
    public function findByOptionA(ParameterOption $option): array
    {
        return $this->findBy(['optionA' => $option]);
    }

    /** @return ForbiddenCombination[] */
    public function findByOptionB(ParameterOption $option): array
    {
        return $this->findBy(['optionB' => $option]);
    }

    public function isForbidden(ParameterOption $optionA, ParameterOption $optionB): bool
    {
        return null !== $this->findOneBy([
            'optionA' => $optionA,
            'optionB' => $optionB,
        ]);
    }
}
