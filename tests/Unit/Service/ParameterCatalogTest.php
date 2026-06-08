<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Parameter;
use App\Entity\ParameterOption;
use App\Repository\ParameterRepository;
use App\Service\ParameterCatalog;
use PHPUnit\Framework\TestCase;

class ParameterCatalogTest extends TestCase
{
    public function testValidValuesReturnsParametersFromRepository(): void
    {
        $parameter1 = new Parameter('parameter1');
        $parameter1->addOption(new ParameterOption($parameter1, 'A', 0));
        $parameter1->addOption(new ParameterOption($parameter1, 'B', 1));
        $parameter1->addOption(new ParameterOption($parameter1, 'C', 2));

        $parameter2 = new Parameter('parameter2');
        $parameter2->addOption(new ParameterOption($parameter2, 'X', 0));
        $parameter2->addOption(new ParameterOption($parameter2, 'Y', 1));
        $parameter2->addOption(new ParameterOption($parameter2, 'Z', 2));

        $repository = $this->createMock(ParameterRepository::class);
        $repository->expects($this->once())
            ->method('findAll')
            ->willReturn([$parameter1, $parameter2]);

        $catalog = new ParameterCatalog($repository);
        $result = $catalog->validValues();

        $this->assertSame([
            'parameter1' => ['A', 'B', 'C'],
            'parameter2' => ['X', 'Y', 'Z'],
        ], $result);
    }

    public function testValidValuesPreservesOptionOrder(): void
    {
        $parameter = new Parameter('param');
        $parameter->addOption(new ParameterOption($parameter, 'Z', 0));
        $parameter->addOption(new ParameterOption($parameter, 'A', 1));
        $parameter->addOption(new ParameterOption($parameter, 'M', 2));

        $repository = $this->createMock(ParameterRepository::class);
        $repository->expects($this->once())
            ->method('findAll')
            ->willReturn([$parameter]);

        $catalog = new ParameterCatalog($repository);
        $result = $catalog->validValues();

        $this->assertSame(['param' => ['Z', 'A', 'M']], $result);
    }

    public function testValidValuesWithEmptyRepository(): void
    {
        $repository = $this->createMock(ParameterRepository::class);
        $repository->expects($this->once())
            ->method('findAll')
            ->willReturn([]);

        $catalog = new ParameterCatalog($repository);
        $result = $catalog->validValues();

        $this->assertSame([], $result);
    }
}
