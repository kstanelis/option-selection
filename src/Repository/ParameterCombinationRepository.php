<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ParameterCombination;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ParameterCombination>
 */
class ParameterCombinationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ParameterCombination::class);
    }

    /**
     * Find combinations matching the selection.
     *
     * The slot identifies the column: 0 = option1, 1 = option2. Each option
     * column structurally belongs to one parameter, so filtering by the
     * column's value alone is sufficient. An empty selection matches all rows.
     *
     * @param array<int, string> $selection Slot (0|1) => selected option value
     * @return ParameterCombination[]
     */
    public function findBySelection(array $selection): array
    {
        $qb = $this->createQueryBuilder('c');

        foreach ($selection as $slot => $optionValue) {
            $alias = 'o' . $slot;
            $qb->innerJoin($slot === 0 ? 'c.option1' : 'c.option2', $alias)
                ->andWhere($alias . '.value = :v' . $slot)
                ->setParameter('v' . $slot, $optionValue);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Collect the distinct option values for one slot across the given combinations.
     *
     * @param ParameterCombination[] $combinations
     * @param int $slot Slot (0 = option1, 1 = option2)
     * @return string[]
     */
    public function getDistinctValues(array $combinations, int $slot): array
    {
        $values = [];
        foreach ($combinations as $combination) {
            $option = $slot === 0 ? $combination->getOption1() : $combination->getOption2();
            $values[] = $option->getValue();
        }

        $values = array_unique($values);
        sort($values);

        return $values;
    }
}
