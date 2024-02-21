<?php

namespace App\Entity;

use App\Repository\CoffeeRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CoffeeRepository::class)]
class Coffee extends SoftDeleteFields
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["getCoffee"])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(["getCoffee"])]
    #[Assert\Length(
        min: 3,
        max: 255,
        minMessage: "The name must be at least {{ limit }} characters long",
        maxMessage: "The name cannot be longer than {{ limit }} characters"
    )]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(['getCoffee'])]
    private ?string $description = null;

    #[ORM\ManyToOne(inversedBy: 'coffees')]
    #[Groups(['getCoffee'])]
    private ?Category $category = null;

    #[ORM\ManyToOne(inversedBy: 'coffees')]
    private ?Taste $taste = null;

    #[ORM\ManyToOne(inversedBy: 'coffees')]
    private ?Bean $bean = null;

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

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function getTaste(): ?Taste
    {
        return $this->taste;
    }

    public function setTaste(?Taste $taste): static
    {
        $this->taste = $taste;

        return $this;
    }

    public function getBean(): ?Bean
    {
        return $this->bean;
    }

    public function setBean(?Bean $bean): static
    {
        $this->bean = $bean;

        return $this;
    }
}
