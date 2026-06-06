<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\ParameterOptions;
use App\Service\ParameterOptionResolverInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class ParameterOptionsProvider implements ProviderInterface
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly ParameterOptionResolverInterface $resolver,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ParameterOptions
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return new ParameterOptions();
        }

        $selection = [];
        $validNames = ['parameter1', 'parameter2'];
        $validValues = [
            'parameter1' => ['A', 'B', 'C'],
            'parameter2' => ['X', 'Y', 'Z'],
        ];

        foreach ($validNames as $paramName) {
            $value = $request->query->get($paramName);
            if ($value !== null) {
                if (!\in_array($value, $validValues[$paramName], true)) {
                    throw new BadRequestHttpException(sprintf(
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
