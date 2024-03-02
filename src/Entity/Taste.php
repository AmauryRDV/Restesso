<?php

namespace App\Entity;

use App\Repository\TasteRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;


#[ORM\Entity(repositoryClass: TasteRepository::class)]
class Taste extends SoftDeleteFields
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['getTaste', 'getCoffee', 'getAllTastes'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\Length(
        min : 2,
        max : 100,
        minMessage : 'The name must be at least {{ limit }} characters long',
        maxMessage: 'The name cannot be longer than {{ limit }} characters',

    )]
    #[Groups(['getTaste', 'getCoffee', 'getAllTastes'])]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    #[Groups(['getTaste', 'getAllTastes'])]
    private ?string $description = null;

    #[ORM\Column]
    #[Groups(['getTaste', 'getAllTastes'])]
    private ?int $intensity = null;

    #[ORM\Column]
    #[Groups(['getTaste', 'getAllTastes'])]
    private ?float $caffeineRate = null;

    #[ORM\OneToMany(
        mappedBy: 'taste',
        targetEntity: Coffee::class,
    )]
    #[Groups(['getTaste'])]
    private Collection $coffees;

    public function __construct()
    {
        $this->coffees = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getIntensity(): ?int
    {
        return $this->intensity;
    }

    public function setIntensity(int $intensity): static
    {
        $this->intensity = $intensity;

        return $this;
    }

    public function getCaffeineRate(): ?float
    {
        return $this->caffeineRate;
    }

    public function setCaffeineRate(float $caffeineRate): static
    {
        $this->caffeineRate = $caffeineRate;

        return $this;
    }

    /**
     * @return Collection<int, Coffee>
     */
    public function getCoffees(): Collection
    {
        return $this->coffees;
    }

    public function addCoffee(Coffee $coffee): static
    {
        if (!$this->coffees->contains($coffee)) {
            $this->coffees->add($coffee);
            $coffee->setTaste($this);
        }

        return $this;
    }

    public function removeCoffee(Coffee $coffee): static
    {
        if ($this->coffees->removeElement($coffee) && $coffee->getTaste() === $this) {
            // set the owning side to null (unless already changed)
            $coffee->setTaste(null);
        }

        return $this;
    }
}
