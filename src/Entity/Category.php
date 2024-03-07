<?php

namespace App\Entity;

use App\Repository\CategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CategoryRepository::class)]
class Category extends SoftDeleteFields
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['getCoffee', 'getCategory', 'getAllCategories'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['getCategory', 'getCoffee', 'getAllCategories', 'createCategory', 'updateCategory'])]
    #[Assert\Length(
        min : 2,
        max : 100,
        minMessage : 'The name must be at least {{ limit }} characters long',
        maxMessage: 'The name cannot be longer than {{ limit }} characters',

    )]
    private ?string $name = null;

    #[ORM\OneToMany(mappedBy: 'category', targetEntity: Coffee::class)]
    #[Groups(['getCategory'])]
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
            $coffee->setCategory($this);
        }

        return $this;
    }

    public function removeCoffee(Coffee $coffee): static
    {
        if ($this->coffees->removeElement($coffee) && $coffee->getCategory() === $this) {
            // set the owning side to null (unless already changed)
            $coffee->setCategory(null);
        }

        return $this;
    }
}
