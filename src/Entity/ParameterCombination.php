<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'parameter_combination')]
#[ORM\UniqueConstraint(name: 'unique_combination', columns: ['option1_id', 'option2_id'])]
class ParameterCombination
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: ParameterOption::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?ParameterOption $option1 = null;

    #[ORM\ManyToOne(targetEntity: ParameterOption::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?ParameterOption $option2 = null;

    public function __construct(ParameterOption $option1, ParameterOption $option2)
    {
        $this->option1 = $option1;
        $this->option2 = $option2;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOption1(): ?ParameterOption
    {
        return $this->option1;
    }

    public function getOption2(): ?ParameterOption
    {
        return $this->option2;
    }
}
