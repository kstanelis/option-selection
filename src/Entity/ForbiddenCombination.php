<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'forbidden_combination')]
#[ORM\UniqueConstraint(name: 'unique_forbidden_pair', columns: ['option_a_id', 'option_b_id'])]
class ForbiddenCombination
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: ParameterOption::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ParameterOption $optionA;

    #[ORM\ManyToOne(targetEntity: ParameterOption::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ParameterOption $optionB;

    public function __construct(ParameterOption $optionA, ParameterOption $optionB)
    {
        $this->optionA = $optionA;
        $this->optionB = $optionB;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOptionA(): ParameterOption
    {
        return $this->optionA;
    }

    public function setOptionA(ParameterOption $optionA): self
    {
        $this->optionA = $optionA;
        return $this;
    }

    public function getOptionB(): ParameterOption
    {
        return $this->optionB;
    }

    public function setOptionB(ParameterOption $optionB): self
    {
        $this->optionB = $optionB;
        return $this;
    }
}
