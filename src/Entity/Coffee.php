<?php

namespace App\Entity;

use App\Repository\CoffeeRepository;
use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: CoffeeRepository::class)]
class Coffee
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["getCoffee"])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(["getCoffee"])]
    private ?string $name = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(["getCoffee"])]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(["getCoffee"])]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(length: 255)]
    #[Groups(["getCoffee"])]
    private ?string $status = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(["getCoffee"])]
    private ?string $description = null;

    #[ORM\ManyToOne(inversedBy: 'coffees')]
    #[Groups(["getCoffee"])]
    private ?Category $category = null;

    #[ORM\ManyToOne(inversedBy: 'coffees')]
    private ?Taste $taste = null;

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

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAtTo(\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function setUpdatedAt(): static
    {
        $this->updatedAt = new DateTime();

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(): static
    {
        $this->createdAt = new DateTime();

        return $this;
    }

    public function setCreatedAtTo(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

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
}
