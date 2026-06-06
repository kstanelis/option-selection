<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Parameter;
use App\Entity\ParameterCombination;
use App\Entity\ParameterOption;
use App\Repository\ParameterCombinationRepository;
use App\Repository\ParameterRepository;
use App\Service\ValidCombinationParameterOptionResolver;
use PHPUnit\Framework\TestCase;

class ValidCombinationParameterOptionResolverTest extends TestCase
{
    private ValidCombinationParameterOptionResolver $resolver;
    private ParameterRepository $parameterRepository;
    private ParameterCombinationRepository $combinationRepository;

    protected function setUp(): void
    {
        $this->parameterRepository = $this->createMock(ParameterRepository::class);
        $this->combinationRepository = $this->createMock(ParameterCombinationRepository::class);

        $this->resolver = new ValidCombinationParameterOptionResolver(
            $this->parameterRepository,
            $this->combinationRepository,
        );
    }

    /**
     * @return Parameter[] [parameter1, parameter2]
     */
    private function twoParameters(): array
    {
        $param1 = new Parameter('parameter1');
        $param2 = new Parameter('parameter2');

        return [$param1, $param2];
    }

    public function testAvailableOptionsWithoutSelectionUsesAllCombinations(): void
    {
        [$param1, $param2] = $this->twoParameters();

        $this->parameterRepository->expects(self::once())
            ->method('findAll')
            ->willReturn([$param1, $param2]);
        $this->combinationRepository->expects(self::once())
            ->method('findBySelection')
            ->with([])
            ->willReturn([]);
        $this->combinationRepository->expects(self::exactly(2))
            ->method('getDistinctValues')
            ->willReturnMap([
                [[], 0, ['A', 'B', 'C']],
                [[], 1, ['X', 'Y', 'Z']],
            ]);

        $result = $this->resolver->availableOptions([]);

        $this->assertSame(['A', 'B', 'C'], $result['parameter1']);
        $this->assertSame(['X', 'Y', 'Z'], $result['parameter2']);
    }

    public function testAvailableOptionsMapsParameter1SelectionToSlotZero(): void
    {
        [$param1, $param2] = $this->twoParameters();

        $this->parameterRepository->expects(self::once())
            ->method('findAll')
            ->willReturn([$param1, $param2]);
        $this->combinationRepository->expects(self::once())
            ->method('findBySelection')
            ->with([0 => 'B'])
            ->willReturn([]);
        $this->combinationRepository->expects(self::exactly(2))
            ->method('getDistinctValues')
            ->willReturn([]);

        $result = $this->resolver->availableOptions(['parameter1' => 'B']);

        $this->assertSame([], $result['parameter1']);
        $this->assertSame([], $result['parameter2']);
    }

    public function testAvailableOptionsMapsParameter2SelectionToSlotOne(): void
    {
        [$param1, $param2] = $this->twoParameters();

        $this->parameterRepository->expects(self::once())
            ->method('findAll')
            ->willReturn([$param1, $param2]);
        $this->combinationRepository->expects(self::once())
            ->method('findBySelection')
            ->with([1 => 'Z'])
            ->willReturn([]);
        $this->combinationRepository->expects(self::exactly(2))
            ->method('getDistinctValues')
            ->willReturn([]);

        $result = $this->resolver->availableOptions(['parameter2' => 'Z']);

        $this->assertSame([], $result['parameter1']);
        $this->assertSame([], $result['parameter2']);
    }

    public function testAvailableOptionsWithBothParameters(): void
    {
        [$param1, $param2] = $this->twoParameters();

        $optionA = new ParameterOption($param1, 'A');
        $optionZ = new ParameterOption($param2, 'Z');
        $combination = new ParameterCombination($optionA, $optionZ);

        $this->parameterRepository->expects(self::once())
            ->method('findAll')
            ->willReturn([$param1, $param2]);
        $this->combinationRepository->expects(self::once())
            ->method('findBySelection')
            ->with([0 => 'A', 1 => 'Z'])
            ->willReturn([$combination]);
        $this->combinationRepository->expects(self::exactly(2))
            ->method('getDistinctValues')
            ->willReturnMap([
                [[$combination], 0, ['A']],
                [[$combination], 1, ['Z']],
            ]);

        $result = $this->resolver->availableOptions(['parameter1' => 'A', 'parameter2' => 'Z']);

        $this->assertSame(['A'], $result['parameter1']);
        $this->assertSame(['Z'], $result['parameter2']);
    }

    public function testAvailableOptionsIgnoresUnknownParameterName(): void
    {
        [$param1, $param2] = $this->twoParameters();

        $this->parameterRepository->expects(self::once())
            ->method('findAll')
            ->willReturn([$param1, $param2]);
        $this->combinationRepository->expects(self::once())
            ->method('findBySelection')
            ->with([])
            ->willReturn([]);
        $this->combinationRepository->expects(self::exactly(2))
            ->method('getDistinctValues')
            ->willReturn([]);

        $result = $this->resolver->availableOptions(['unknown' => 'value']);

        $this->assertArrayHasKey('parameter1', $result);
        $this->assertArrayHasKey('parameter2', $result);
    }
}
