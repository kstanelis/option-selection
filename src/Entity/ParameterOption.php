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
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Parameter::class, inversedBy: 'options')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Parameter $parameter = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $value;

    public function __construct(Parameter $parameter, string $value)
    {
        $this->parameter = $parameter;
        $this->value = $value;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getParameter(): ?Parameter
    {
        return $this->parameter;
    }

    public function setParameter(?Parameter $parameter): self
    {
        $this->parameter = $parameter;
        return $this;
    }

    public function getValue(): string
    {
        return $this->value;
    }
}
