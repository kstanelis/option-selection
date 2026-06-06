<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\StaticParameterOptionResolver;
use PHPUnit\Framework\TestCase;

class StaticParameterOptionResolverTest extends TestCase
{
    private StaticParameterOptionResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new StaticParameterOptionResolver();
    }

    public function testReturnsAllOptionsWithEmptySelection(): void
    {
        $result = $this->resolver->availableOptions([]);

        $this->assertEquals(['A', 'B', 'C'], $result['parameter1']);
        $this->assertEquals(['X', 'Y', 'Z'], $result['parameter2']);
    }

    public function testReturnsAllOptionsWithParameter1Selection(): void
    {
        $result = $this->resolver->availableOptions(['parameter1' => 'A']);

        $this->assertEquals(['A', 'B', 'C'], $result['parameter1']);
        $this->assertEquals(['X', 'Y', 'Z'], $result['parameter2']);
    }

    public function testReturnsAllOptionsWithBothParametersSelected(): void
    {
        $result = $this->resolver->availableOptions([
            'parameter1' => 'B',
            'parameter2' => 'Y',
        ]);

        $this->assertEquals(['A', 'B', 'C'], $result['parameter1']);
        $this->assertEquals(['X', 'Y', 'Z'], $result['parameter2']);
    }
}
