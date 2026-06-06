<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'parameter_option')]
#[ORM\UniqueConstraint(name: 'unique_parameter_value', columns: ['parameter_id', 'value'])]
class ParameterOption
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Parameter::class, inversedBy: 'options')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Parameter $parameter;

    #[ORM\Column(length: 255)]
    private string $value;

    #[ORM\Column]
    private int $position;

    public function __construct(Parameter $parameter, string $value, int $position)
    {
        $this->parameter = $parameter;
        $this->value = $value;
        $this->position = $position;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getParameter(): Parameter
    {
        return $this->parameter;
    }

    public function setParameter(Parameter $parameter): self
    {
        $this->parameter = $parameter;
        return $this;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function setValue(string $value): self
    {
        $this->value = $value;
        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): self
    {
        $this->position = $position;
        return $this;
    }
}
