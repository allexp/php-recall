<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/** Представляет раскрываемый раздел учебного материала. */
#[ORM\Entity]
#[ORM\Table(
    name: 'material_sections',
    uniqueConstraints: [new ORM\UniqueConstraint(name: 'uniq_material_sections_material_slug', columns: ['material_id', 'slug'])],
    indexes: [new ORM\Index(name: 'idx_material_sections_material', columns: ['material_id', 'position'])],
)]
class MaterialSection
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Material::class, inversedBy: 'sections')]
    #[ORM\JoinColumn(name: 'material_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Material $material = null;

    #[ORM\Column(type: Types::TEXT)]
    private string $title = '';

    #[ORM\Column(type: Types::TEXT)]
    private string $slug = '';

    #[ORM\Column(type: Types::TEXT, options: ['default' => ''])]
    private string $description = '';

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    private int $position = 0;

    /** @var Collection<int, CodeExample> */
    #[ORM\OneToMany(targetEntity: CodeExample::class, mappedBy: 'section', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC', 'id' => 'ASC'])]
    private Collection $codeExamples;

    public function __construct()
    {
        $this->codeExamples = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMaterial(): ?Material
    {
        return $this->material;
    }

    public function setMaterial(Material $material): self
    {
        $this->material = $material;
        foreach ($this->codeExamples as $example) {
            $example->setMaterial($material);
        }

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

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

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

    /** @return Collection<int, CodeExample> */
    public function getCodeExamples(): Collection
    {
        return $this->codeExamples;
    }

    public function addCodeExample(CodeExample $example): self
    {
        if (!$this->codeExamples->contains($example)) {
            $this->codeExamples->add($example);
            $example->setSection($this);
            if ($this->material !== null) {
                $example->setMaterial($this->material);
            }
        }

        return $this;
    }

    public function removeCodeExample(CodeExample $example): self
    {
        if ($this->codeExamples->removeElement($example) && $example->getSection() === $this) {
            $example->setSection(null);
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->title;
    }
}
