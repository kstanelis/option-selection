<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\ParameterCombinationRepository;
use App\Repository\ParameterRepository;

class ValidCombinationParameterOptionResolver implements ParameterOptionResolverInterface
{
    public function __construct(
        private readonly ParameterRepository $parameterRepository,
        private readonly ParameterCombinationRepository $combinationRepository,
    ) {
    }

    public function availableOptions(array $selection): array
    {
        // Parameters are positional: the first maps to the option1 column
        // (slot 0), the second to option2 (slot 1).
        $parameters = array_values($this->parameterRepository->findAll());

        $nameToSlot = [];
        foreach ($parameters as $slot => $parameter) {
            $nameToSlot[$parameter->getName()] = $slot;
        }

        $selectionBySlot = [];
        foreach ($selection as $paramName => $optionValue) {
            if (isset($nameToSlot[$paramName])) {
                $selectionBySlot[$nameToSlot[$paramName]] = $optionValue;
            }
        }

        $combinations = $this->combinationRepository->findBySelection($selectionBySlot);

        $result = [];
        foreach ($parameters as $slot => $parameter) {
            $result[$parameter->getName()] = $this->combinationRepository->getDistinctValues($combinations, $slot);
        }

        return $result;
    }
}
