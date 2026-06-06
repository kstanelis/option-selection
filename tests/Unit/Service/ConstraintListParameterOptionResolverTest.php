<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\ForbiddenCombination;
use App\Entity\Parameter;
use App\Entity\ParameterOption;
use App\Repository\ForbiddenCombinationRepository;
use App\Repository\ParameterRepository;
use App\Service\ConstraintListParameterOptionResolver;
use PHPUnit\Framework\TestCase;

class ConstraintListParameterOptionResolverTest extends TestCase
{
    private ParameterRepository $parameterRepository;
    private ForbiddenCombinationRepository $forbiddenRepository;
    private ConstraintListParameterOptionResolver $resolver;

    protected function setUp(): void
    {
        $this->parameterRepository = $this->createMock(ParameterRepository::class);
        $this->forbiddenRepository = $this->createMock(ForbiddenCombinationRepository::class);
        $this->resolver = new ConstraintListParameterOptionResolver(
            $this->parameterRepository,
            $this->forbiddenRepository,
        );
    }

    public function testNoSelectionReturnsAllOptions(): void
    {
        $parameter1 = new Parameter('parameter1');
        $optionA = new ParameterOption($parameter1, 'A', 0);
        $optionB = new ParameterOption($parameter1, 'B', 1);
        $optionC = new ParameterOption($parameter1, 'C', 2);
        $parameter1->addOption($optionA);
        $parameter1->addOption($optionB);
        $parameter1->addOption($optionC);

        $parameter2 = new Parameter('parameter2');
        $optionX = new ParameterOption($parameter2, 'X', 0);
        $optionY = new ParameterOption($parameter2, 'Y', 1);
        $optionZ = new ParameterOption($parameter2, 'Z', 2);
        $parameter2->addOption($optionX);
        $parameter2->addOption($optionY);
        $parameter2->addOption($optionZ);

        $this->parameterRepository->method('findAll')->willReturn([$parameter1, $parameter2]);
        $this->forbiddenRepository->method('isForbidden')->willReturn(false);

        $result = $this->resolver->availableOptions([]);

        $this->assertEquals(['A', 'B', 'C'], $result['parameter1']);
        $this->assertEquals(['X', 'Y', 'Z'], $result['parameter2']);
    }

    public function testSelectedParameterReturnsOnlySelectedValue(): void
    {
        $parameter1 = new Parameter('parameter1');
        $optionA = new ParameterOption($parameter1, 'A', 0);
        $optionB = new ParameterOption($parameter1, 'B', 1);
        $optionC = new ParameterOption($parameter1, 'C', 2);
        $parameter1->addOption($optionA);
        $parameter1->addOption($optionB);
        $parameter1->addOption($optionC);

        $parameter2 = new Parameter('parameter2');
        $optionX = new ParameterOption($parameter2, 'X', 0);
        $optionY = new ParameterOption($parameter2, 'Y', 1);
        $optionZ = new ParameterOption($parameter2, 'Z', 2);
        $parameter2->addOption($optionX);
        $parameter2->addOption($optionY);
        $parameter2->addOption($optionZ);

        $this->parameterRepository->method('findAll')->willReturn([$parameter1, $parameter2]);
        $this->forbiddenRepository->method('isForbidden')->willReturn(false);

        $result = $this->resolver->availableOptions(['parameter1' => 'B']);

        $this->assertEquals(['B'], $result['parameter1']);
        $this->assertEquals(['X', 'Y', 'Z'], $result['parameter2']);
    }

    public function testForbiddenPairAYFiltersOptionY(): void
    {
        $parameter1 = new Parameter('parameter1');
        $optionA = new ParameterOption($parameter1, 'A', 0);
        $optionB = new ParameterOption($parameter1, 'B', 1);
        $optionC = new ParameterOption($parameter1, 'C', 2);
        $parameter1->addOption($optionA);
        $parameter1->addOption($optionB);
        $parameter1->addOption($optionC);

        $parameter2 = new Parameter('parameter2');
        $optionX = new ParameterOption($parameter2, 'X', 0);
        $optionY = new ParameterOption($parameter2, 'Y', 1);
        $optionZ = new ParameterOption($parameter2, 'Z', 2);
        $parameter2->addOption($optionX);
        $parameter2->addOption($optionY);
        $parameter2->addOption($optionZ);

        $this->parameterRepository->method('findAll')->willReturn([$parameter1, $parameter2]);
        $this->parameterRepository->method('findByName')->willReturnMap([
            ['parameter1', $parameter1],
            ['parameter2', $parameter2],
        ]);

        $this->forbiddenRepository->method('isForbidden')
            ->willReturnCallback(function ($opt1, $opt2) use ($optionA, $optionY) {
                return ($opt1 === $optionA && $opt2 === $optionY) || ($opt1 === $optionY && $opt2 === $optionA);
            });

        $result = $this->resolver->availableOptions(['parameter1' => 'A']);

        $this->assertEquals(['A'], $result['parameter1']);
        $this->assertEquals(['X', 'Z'], $result['parameter2']);
    }

    public function testForbiddenPairCZFiltersOptionZ(): void
    {
        $parameter1 = new Parameter('parameter1');
        $optionA = new ParameterOption($parameter1, 'A', 0);
        $optionB = new ParameterOption($parameter1, 'B', 1);
        $optionC = new ParameterOption($parameter1, 'C', 2);
        $parameter1->addOption($optionA);
        $parameter1->addOption($optionB);
        $parameter1->addOption($optionC);

        $parameter2 = new Parameter('parameter2');
        $optionX = new ParameterOption($parameter2, 'X', 0);
        $optionY = new ParameterOption($parameter2, 'Y', 1);
        $optionZ = new ParameterOption($parameter2, 'Z', 2);
        $parameter2->addOption($optionX);
        $parameter2->addOption($optionY);
        $parameter2->addOption($optionZ);

        $this->parameterRepository->method('findAll')->willReturn([$parameter1, $parameter2]);
        $this->parameterRepository->method('findByName')->willReturnMap([
            ['parameter1', $parameter1],
            ['parameter2', $parameter2],
        ]);

        $this->forbiddenRepository->method('isForbidden')
            ->willReturnCallback(function ($opt1, $opt2) use ($optionC, $optionZ) {
                return ($opt1 === $optionC && $opt2 === $optionZ) || ($opt1 === $optionZ && $opt2 === $optionC);
            });

        $result = $this->resolver->availableOptions(['parameter2' => 'Z']);

        $this->assertEquals(['A', 'B'], $result['parameter1']);
        $this->assertEquals(['Z'], $result['parameter2']);
    }

    public function testIgnoresUnknownParameter(): void
    {
        $parameter1 = new Parameter('parameter1');
        $optionA = new ParameterOption($parameter1, 'A', 0);
        $optionB = new ParameterOption($parameter1, 'B', 1);
        $optionC = new ParameterOption($parameter1, 'C', 2);
        $parameter1->addOption($optionA);
        $parameter1->addOption($optionB);
        $parameter1->addOption($optionC);

        $parameter2 = new Parameter('parameter2');
        $optionX = new ParameterOption($parameter2, 'X', 0);
        $optionY = new ParameterOption($parameter2, 'Y', 1);
        $optionZ = new ParameterOption($parameter2, 'Z', 2);
        $parameter2->addOption($optionX);
        $parameter2->addOption($optionY);
        $parameter2->addOption($optionZ);

        $this->parameterRepository->method('findAll')->willReturn([$parameter1, $parameter2]);
        $this->parameterRepository->method('findByName')->willReturnMap([
            ['parameter1', $parameter1],
            ['parameter2', $parameter2],
            ['parameter3', null],
        ]);
        $this->forbiddenRepository->method('isForbidden')->willReturn(false);

        $result = $this->resolver->availableOptions(['parameter3' => 'foo']);

        $this->assertEquals(['A', 'B', 'C'], $result['parameter1']);
        $this->assertEquals(['X', 'Y', 'Z'], $result['parameter2']);
    }

    public function testForbiddenDualSelectionReturnsEmptyArrays(): void
    {
        $parameter1 = new Parameter('parameter1');
        $optionA = new ParameterOption($parameter1, 'A', 0);
        $optionB = new ParameterOption($parameter1, 'B', 1);
        $optionC = new ParameterOption($parameter1, 'C', 2);
        $parameter1->addOption($optionA);
        $parameter1->addOption($optionB);
        $parameter1->addOption($optionC);

        $parameter2 = new Parameter('parameter2');
        $optionX = new ParameterOption($parameter2, 'X', 0);
        $optionY = new ParameterOption($parameter2, 'Y', 1);
        $optionZ = new ParameterOption($parameter2, 'Z', 2);
        $parameter2->addOption($optionX);
        $parameter2->addOption($optionY);
        $parameter2->addOption($optionZ);

        $this->parameterRepository->method('findAll')->willReturn([$parameter1, $parameter2]);
        $this->parameterRepository->method('findByName')->willReturnMap([
            ['parameter1', $parameter1],
            ['parameter2', $parameter2],
        ]);

        $this->forbiddenRepository->method('isForbidden')
            ->willReturnCallback(function ($opt1, $opt2) use ($optionA, $optionY) {
                return ($opt1 === $optionA && $opt2 === $optionY) || ($opt1 === $optionY && $opt2 === $optionA);
            });

        $result = $this->resolver->availableOptions(['parameter1' => 'A', 'parameter2' => 'Y']);

        $this->assertEquals([], $result['parameter1']);
        $this->assertEquals([], $result['parameter2']);
    }

    public function testValidDualSelectionReturnsBothSelectedValues(): void
    {
        $parameter1 = new Parameter('parameter1');
        $optionA = new ParameterOption($parameter1, 'A', 0);
        $optionB = new ParameterOption($parameter1, 'B', 1);
        $optionC = new ParameterOption($parameter1, 'C', 2);
        $parameter1->addOption($optionA);
        $parameter1->addOption($optionB);
        $parameter1->addOption($optionC);

        $parameter2 = new Parameter('parameter2');
        $optionX = new ParameterOption($parameter2, 'X', 0);
        $optionY = new ParameterOption($parameter2, 'Y', 1);
        $optionZ = new ParameterOption($parameter2, 'Z', 2);
        $parameter2->addOption($optionX);
        $parameter2->addOption($optionY);
        $parameter2->addOption($optionZ);

        $this->parameterRepository->method('findAll')->willReturn([$parameter1, $parameter2]);
        $this->parameterRepository->method('findByName')->willReturnMap([
            ['parameter1', $parameter1],
            ['parameter2', $parameter2],
        ]);

        $this->forbiddenRepository->method('isForbidden')->willReturn(false);

        $result = $this->resolver->availableOptions(['parameter1' => 'A', 'parameter2' => 'X']);

        $this->assertEquals(['A'], $result['parameter1']);
        $this->assertEquals(['X'], $result['parameter2']);
    }
}
