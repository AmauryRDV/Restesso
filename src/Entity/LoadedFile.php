<?php

namespace App\Entity;

use App\Repository\LoadedFileRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: LoadedFileRepository::class)]
class LoadedFile extends SoftDeleteFields
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['getLoadedFile', 'getCoffee', 'getAllLoadedFiles'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['getLoadedFile', 'getCoffee', 'getAllLoadedFiles', 'createLoadedFile', 'updateLoadedFile'])]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    #[Groups(['getLoadedFile', 'getAllLoadedFiles', 'createLoadedFile', 'updateLoadedFile'])]
    private ?string $realName = null;

    #[ORM\Column(length: 255)]
    #[Groups(['getLoadedFile', 'getAllLoadedFiles', 'createLoadedFile', 'updateLoadedFile'])]
    private ?string $realPath = null;

    #[ORM\Column(length: 255)]
    #[Groups(['getLoadedFile', 'getAllLoadedFiles', 'createLoadedFile', 'updateLoadedFile'])]
    private ?string $publicPath = null;

    #[ORM\Column(length: 255)]
    #[Groups(['getLoadedFile', 'getAllLoadedFiles', 'createLoadedFile', 'updateLoadedFile'])]
    private ?string $mimeType = null;

    #[Vich\UploadableField(mapping:'pictures', fileNameProperty:'realPath')]
    private $file;
    
    #[ORM\OneToMany(mappedBy: 'coffeeImage', targetEntity: Coffee::class)]
    #[Groups(['getLoadedFile'])]
    private Collection $coffees;

    public function __construct()
    {
        $this->coffees = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRealName(): ?string
    {
        return $this->realName;
    }

    public function setRealName(string $realName): static
    {
        $this->realName = $realName;

        return $this;
    }

    public function getRealPath(): ?string
    {
        return $this->realPath;
    }

    public function setRealPath(string $realPath): static
    {
        $this->realPath = $realPath;

        return $this;
    }

    public function getPublicPath(): ?string
    {
        return $this->publicPath;
    }

    public function setPublicPath(string $publicPath): static
    {
        $this->publicPath = $publicPath;

        return $this;
    }

    public function getMimeType(): ?string
    {
        return $this->mimeType;
    }

    public function setMimeType(string $mimeType): static
    {
        $this->mimeType = $mimeType;

        return $this;
    }

    public function getFile(): ?File
    {
        return $this->file;
    }

    public function setFile(File $file): static
    {
        $this->file = $file;

        return $this;
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
            $coffee->setCoffeeImage($this);
        }

        return $this;
    }

    public function removeCoffee(Coffee $coffee): static
    {
        if ($this->coffees->removeElement($coffee) && $coffee->getCoffeeImage() === $this) {
            $coffee->setCoffeeImage(null);
        }

        return $this;
    }
}
