<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\ParameterRepository;

class ParameterCatalog
{
    public function __construct(
        private readonly ParameterRepository $parameterRepository,
    ) {
    }

    /**
     * @return array<string, list<string>>
     */
    public function validValues(): array
    {
        $result = [];
        $parameters = $this->parameterRepository->findAll();

        foreach ($parameters as $parameter) {
            $values = [];
            foreach ($parameter->getOptions() as $option) {
                $values[] = $option->getValue();
            }
            $result[$parameter->getName()] = $values;
        }

        return $result;
    }
}
