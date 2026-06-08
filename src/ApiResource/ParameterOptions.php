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
                    ],
                    required: false,
                ),
                'parameter2' => new QueryParameter(
                    schema: [
                        'type' => 'string',
                    ],
                    required: false,
                ),
            ],
        ),
    ],
)]
class ParameterOptions
{
    /** @param array<string> $parameter1
        @param array<string> $parameter2 */
    public function __construct(
        public array $parameter1 = [],
        public array $parameter2 = [],
    ) {
    }
}
