<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\ParameterOptions;
use App\Service\ParameterCatalog;
use App\Service\ParameterOptionResolverInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/** @implements ProviderInterface<ParameterOptions> */
class ParameterOptionsProvider implements ProviderInterface
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly ParameterOptionResolverInterface $resolver,
        private readonly ParameterCatalog $catalog,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ParameterOptions
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return new ParameterOptions();
        }

        $selection = [];
        $validValues = $this->catalog->validValues();

        foreach ($validValues as $paramName => $allowedValues) {
            $value = $request->query->get($paramName);
            if ($value !== null) {
                if (!\in_array($value, $allowedValues, true)) {
                    throw new UnprocessableEntityHttpException(sprintf(
                        'Invalid value "%s" for parameter "%s"',
                        $value,
                        $paramName,
                    ));
                }
                $selection[$paramName] = $value;
            }
        }

        $options = $this->resolver->availableOptions($selection);

        return new ParameterOptions(
            parameter1: $options['parameter1'] ?? [],
            parameter2: $options['parameter2'] ?? [],
        );
    }
}
