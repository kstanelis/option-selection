<?php

declare(strict_types=1);

namespace App\Service;

class StaticParameterOptionResolver implements ParameterOptionResolverInterface
{
    public function availableOptions(array $selection): array
    {
        return [
            'parameter1' => ['A', 'B', 'C'],
            'parameter2' => ['X', 'Y', 'Z'],
        ];
    }
}
