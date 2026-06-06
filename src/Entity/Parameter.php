<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'parameter')]
class Parameter
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    private string $name;

    /** @var Collection<int, ParameterOption> */
    #[ORM\OneToMany(mappedBy: 'parameter', targetEntity: ParameterOption::class, cascade: ['persist', 'remove'])]
    private Collection $options;

    public function __construct(string $name)
    {
        $this->name = $name;
        $this->options = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getOptions(): Collection
    {
        return $this->options;
    }

    public function addOption(ParameterOption $option): self
    {
        if (!$this->options->contains($option)) {
            $this->options->add($option);
            $option->setParameter($this);
        }
        return $this;
    }
}
