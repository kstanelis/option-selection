<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\ParameterOption;
use App\Repository\ForbiddenCombinationRepository;
use App\Repository\ParameterRepository;

class ConstraintListParameterOptionResolver implements ParameterOptionResolverInterface
{
    public function __construct(
        private readonly ParameterRepository $parameterRepository,
        private readonly ForbiddenCombinationRepository $forbiddenRepository,
    ) {
    }

    public function availableOptions(array $selection): array
    {
        $result = [];
        $parameters = $this->parameterRepository->findAll();

        foreach ($parameters as $parameter) {
            $parameterName = $parameter->getName();

            if (isset($selection[$parameterName])) {
                $selectedValue = $selection[$parameterName];
                $selectedOption = null;
                foreach ($parameter->getOptions() as $option) {
                    if ($option->getValue() === $selectedValue) {
                        $selectedOption = $option;
                        break;
                    }
                }

                if (null === $selectedOption) {
                    $result[$parameterName] = [];
                } else {
                    $otherSelections = array_diff_key($selection, [$parameterName => true]);
                    if ($this->isOptionAvailable($selectedOption, $otherSelections)) {
                        $result[$parameterName] = [$selectedValue];
                    } else {
                        $result[$parameterName] = [];
                    }
                }
            } else {
                $availableValues = [];
                foreach ($parameter->getOptions() as $option) {
                    if ($this->isOptionAvailable($option, $selection)) {
                        $availableValues[] = $option->getValue();
                    }
                }
                $result[$parameterName] = $availableValues;
            }
        }

        return $result;
    }

    /** @param array<string, string> $selection */
    private function isOptionAvailable(ParameterOption $candidateOption, array $selection): bool
    {
        foreach ($selection as $parameterName => $selectedValue) {
            $selectedParameter = $this->parameterRepository->findByName($parameterName);
            if (null === $selectedParameter) {
                continue;
            }

            $selectedOption = null;
            foreach ($selectedParameter->getOptions() as $option) {
                if ($option->getValue() === $selectedValue) {
                    $selectedOption = $option;
                    break;
                }
            }

            if (null === $selectedOption) {
                continue;
            }

            if (
                $this->forbiddenRepository->isForbidden($candidateOption, $selectedOption) ||
                $this->forbiddenRepository->isForbidden($selectedOption, $candidateOption)
            ) {
                return false;
            }
        }

        return true;
    }
}
