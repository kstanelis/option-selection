<?php

declare(strict_types=1);

namespace App\ApiPlatform;

use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use App\ApiResource\ParameterOptions;
use App\Service\ParameterCatalog;

class ParameterEnumDecorator implements ResourceMetadataCollectionFactoryInterface
{
    public function __construct(
        private readonly ResourceMetadataCollectionFactoryInterface $decorated,
        private readonly ParameterCatalog $catalog,
    ) {
    }

    public function create(string $resourceClass): ResourceMetadataCollection
    {
        $collection = $this->decorated->create($resourceClass);

        if ($resourceClass !== ParameterOptions::class) {
            return $collection;
        }

        // Metadata is built during cache warmup / route loading, which can run
        // before the schema exists (fresh install, CI bootstrap). If the catalog
        // is unreachable, leave the declared schema untouched rather than break boot.
        try {
            $validValues = $this->catalog->validValues();
        } catch (\Throwable) {
            return $collection;
        }

        foreach ($collection as $i => $resource) {
            $operations = $resource->getOperations();
            if (null === $operations) {
                continue;
            }

            foreach ($operations as $operationName => $operation) {
                $operations->add($operationName, $this->injectEnums($operation, $validValues));
            }

            $collection[$i] = $resource->withOperations($operations);
        }

        return $collection;
    }

    /**
     * @param array<string, list<string>> $validValues
     */
    private function injectEnums(HttpOperation $operation, array $validValues): HttpOperation
    {
        $parameters = $operation->getParameters();
        if (null === $parameters) {
            return $operation;
        }

        foreach ($validValues as $paramName => $allowedValues) {
            $parameter = $parameters->get($paramName);
            if (null === $parameter) {
                continue;
            }
            $schema = $parameter->getSchema() ?? [];
            $schema['enum'] = $allowedValues;
            $parameters->add($paramName, $parameter->withSchema($schema));
        }

        return $operation->withParameters($parameters);
    }
}
