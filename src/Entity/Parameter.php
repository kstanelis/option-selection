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
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    private string $name;

    /** @var Collection<int, ParameterOption> */
    #[ORM\OneToMany(
        targetEntity: ParameterOption::class,
        mappedBy: 'parameter',
        cascade: ['persist', 'remove'],
        orphanRemoval: true,
    )]
    #[ORM\OrderBy(['position' => 'ASC'])]
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

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    /** @return Collection<int, ParameterOption> */
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

    public function removeOption(ParameterOption $option): self
    {
        $this->options->removeElement($option);
        return $this;
    }
}
