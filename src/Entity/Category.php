<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/** Группирует материалы внутри одного системного типа. */
#[ORM\Entity]
#[ORM\Table(
    name: 'categories',
    uniqueConstraints: [new ORM\UniqueConstraint(name: 'uniq_categories_type_code', columns: ['type_id', 'code'])],
    indexes: [new ORM\Index(name: 'idx_categories_type', columns: ['type_id', 'position'])],
)]
#[UniqueEntity(fields: ['type', 'code'], message: 'Категория с таким кодом уже существует для выбранного типа.')]
class Category
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: MaterialType::class, inversedBy: 'categories')]
    #[ORM\JoinColumn(name: 'type_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull(message: 'Выберите тип материала.')]
    private ?MaterialType $type = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'Укажите код категории.')]
    #[Assert\Regex(pattern: '/^[a-z0-9]+(?:-[a-z0-9]+)*$/', message: 'Используйте строчные латинские буквы, цифры и дефисы.')]
    private string $code = '';

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'Укажите название категории.')]
    private string $title = '';

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    #[Assert\PositiveOrZero(message: 'Позиция не может быть отрицательной.')]
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
