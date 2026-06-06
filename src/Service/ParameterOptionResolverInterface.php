<?php

declare(strict_types=1);

namespace App\Service;

interface ParameterOptionResolverInterface
{
    /**
     * Resolve available options for given parameter selections.
     *
     * @param array<string, string> $selection Key-value pairs of parameter names and selected values
     * @return array<string, string[]> Available options per parameter name
     */
    public function availableOptions(array $selection): array;
}
