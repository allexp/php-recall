<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/** Группирует материалы внутри одного системного типа. */
#[ORM\Entity]
#[ORM\Table(
    name: 'categories',
    uniqueConstraints: [new ORM\UniqueConstraint(name: 'uniq_categories_type_code', columns: ['type_id', 'code'])],
    indexes: [new ORM\Index(name: 'idx_categories_type', columns: ['type_id', 'position'])],
)]
class Category
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: MaterialType::class, inversedBy: 'categories')]
    #[ORM\JoinColumn(name: 'type_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?MaterialType $type = null;

    #[ORM\Column(type: Types::TEXT)]
    private string $code = '';

    #[ORM\Column(type: Types::TEXT)]
    private string $title = '';

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    private int $position = 0;

    /** @var Collection<int, Material> */
    #[ORM\OneToMany(targetEntity: Material::class, mappedBy: 'category')]
    #[ORM\OrderBy(['position' => 'ASC', 'id' => 'ASC'])]
    private Collection $materials;

    public function __construct()
    {
        $this->materials = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): ?MaterialType
    {
        return $this->type;
    }

    public function setType(MaterialType $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

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

    /** @return Collection<int, Material> */
    public function getMaterials(): Collection
    {
        return $this->materials;
    }

    public function addMaterial(Material $material): self
    {
        if (!$this->materials->contains($material)) {
            $this->materials->add($material);
            $material->setCategory($this);
        }

        return $this;
    }

    public function removeMaterial(Material $material): self
    {
        if ($this->materials->removeElement($material) && $material->getCategory() === $this) {
            $material->setCategory(null);
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->title;
    }
}
