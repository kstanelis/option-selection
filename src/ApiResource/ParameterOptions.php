<?php

declare(strict_types=1);

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\QueryParameter;

#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/parameter',
            provider: 'App\State\ParameterOptionsProvider',
            parameters: [
                'parameter1' => new QueryParameter(
                    schema: [
                        'type' => 'string',
                        'enum' => ['A', 'B', 'C'],
                    ],
                    required: false,
                ),
                'parameter2' => new QueryParameter(
                    schema: [
                        'type' => 'string',
                        'enum' => ['X', 'Y', 'Z'],
                    ],
                    required: false,
                ),
            ],
        ),
    ],
)]
class ParameterOptions
{
    public function __construct(
        public array $parameter1 = [],
        public array $parameter2 = [],
    ) {
    }
}
