<?php

namespace App\Entity;

use App\Repository\BeanRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;


#[ORM\Entity(repositoryClass: BeanRepository::class)]
class Bean extends SoftDeleteFields
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['getCoffee', 'getBean', 'getAllBeans'])]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Groups(['getBean', 'getCoffee', 'getAllBeans', 'createBean', 'updateBean'])]
    #[Assert\Length(
        min : 2,
        max : 100,
        minMessage : 'The name must be at least {{ limit }} characters long',
        maxMessage: 'The name cannot be longer than {{ limit }} characters',

    )]
    private ?string $name = null;

    #[ORM\Column(length: 200, nullable: true)]
    #[Groups(['getBean', 'getAllBeans', 'createBean', 'updateBean'])]
    private ?string $origin = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['getBean', 'getAllBeans', 'createBean', 'updateBean'])]
    private ?string $description = null;

    #[ORM\OneToMany(
        mappedBy: 'bean',
        targetEntity: Coffee::class,
        orphanRemoval: true
    )]
    #[Groups(['getBean'])]
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

    public function getOrigin(): ?string
    {
        return $this->origin;
    }

    public function setOrigin(?string $origin): static
    {
        $this->origin = $origin;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

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
            $coffee->setBean($this);
        }

        return $this;
    }

    public function removeCoffee(Coffee $coffee): static
    {
        if ($this->coffees->removeElement($coffee) && $coffee->getBean() === $this) {
            // set the owning side to null (unless already changed)
            $coffee->setBean(null);
        }

        return $this;
    }
}
