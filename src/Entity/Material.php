<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/** Представляет одну учебную карточку в каталоге. */
#[ORM\Entity]
#[ORM\Table(
    name: 'materials',
    uniqueConstraints: [new ORM\UniqueConstraint(name: 'uniq_materials_type_slug', columns: ['type_id', 'slug'])],
    indexes: [
        new ORM\Index(name: 'idx_materials_category', columns: ['category_id']),
        new ORM\Index(name: 'idx_materials_type_title', columns: ['type_id', 'title']),
    ],
)]
#[ORM\HasLifecycleCallbacks]
class Material
{
    public const FORMAT_HTML = 'html';
    public const FORMAT_MARKDOWN = 'markdown';
    public const FORMAT_TEXT = 'text';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: MaterialType::class, inversedBy: 'materials')]
    #[ORM\JoinColumn(name: 'type_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?MaterialType $type = null;

    #[ORM\ManyToOne(targetEntity: Category::class, inversedBy: 'materials')]
    #[ORM\JoinColumn(name: 'category_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Category $category = null;

    #[ORM\Column(type: Types::TEXT)]
    private string $title = '';

    #[ORM\Column(type: Types::TEXT)]
    private string $slug = '';

    #[ORM\Column(type: Types::TEXT, options: ['default' => ''])]
    private string $definition = '';

    #[ORM\Column(name: 'short_description', type: Types::TEXT, options: ['default' => ''])]
    private string $shortDescription = '';

    #[ORM\Column(name: 'full_description', type: Types::TEXT, options: ['default' => ''])]
    private string $fullDescription = '';

    #[ORM\Column(name: 'content_format', type: Types::TEXT, options: ['default' => self::FORMAT_HTML])]
    private string $contentFormat = self::FORMAT_HTML;

    #[ORM\Column(name: 'source_url', type: Types::TEXT, nullable: true)]
    private ?string $sourceUrl = null;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    private int $position = 0;

    #[ORM\Column(name: 'created_at', type: Types::TEXT)]
    private string $createdAt;

    #[ORM\Column(name: 'updated_at', type: Types::TEXT)]
    private string $updatedAt;

    /** @var Collection<int, CodeExample> */
    #[ORM\OneToMany(targetEntity: CodeExample::class, mappedBy: 'material', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC', 'id' => 'ASC'])]
    private Collection $codeExamples;

    /** @var Collection<int, MaterialSection> */
    #[ORM\OneToMany(targetEntity: MaterialSection::class, mappedBy: 'material', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC', 'id' => 'ASC'])]
    private Collection $sections;

    public function __construct()
    {
        $now = (new \DateTimeImmutable())->format(DATE_ATOM);
        $this->createdAt = $now;
        $this->updatedAt = $now;
        $this->codeExamples = new ArrayCollection();
        $this->sections = new ArrayCollection();
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

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): self
    {
        $this->category = $category;

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

    public function getDefinition(): string
    {
        return $this->definition;
    }

    public function setDefinition(string $definition): self
    {
        $this->definition = $definition;

        return $this;
    }

    public function getShortDescription(): string
    {
        return $this->shortDescription;
    }

    public function setShortDescription(string $shortDescription): self
    {
        $this->shortDescription = $shortDescription;

        return $this;
    }

    public function getFullDescription(): string
    {
        return $this->fullDescription;
    }

    public function setFullDescription(string $fullDescription): self
    {
        $this->fullDescription = $fullDescription;

        return $this;
    }

    public function getContentFormat(): string
    {
        return $this->contentFormat;
    }

    public function setContentFormat(string $contentFormat): self
    {
        $this->contentFormat = $contentFormat;

        return $this;
    }

    public function getSourceUrl(): ?string
    {
        return $this->sourceUrl;
    }

    public function setSourceUrl(?string $sourceUrl): self
    {
        $this->sourceUrl = $sourceUrl;

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

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function setCreatedAt(string $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): string
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(string $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

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
            $example->setMaterial($this);
        }

        return $this;
    }

    public function removeCodeExample(CodeExample $example): self
    {
        $this->codeExamples->removeElement($example);

        return $this;
    }

    /** @return Collection<int, MaterialSection> */
    public function getSections(): Collection
    {
        return $this->sections;
    }

    public function addSection(MaterialSection $section): self
    {
        if (!$this->sections->contains($section)) {
            $this->sections->add($section);
            $section->setMaterial($this);
        }

        return $this;
    }

    public function removeSection(MaterialSection $section): self
    {
        $this->sections->removeElement($section);

        return $this;
    }

    #[ORM\PreUpdate]
    public function updateTimestamp(): void
    {
        $this->updatedAt = (new \DateTimeImmutable())->format(DATE_ATOM);
    }

    public function __toString(): string
    {
        return $this->title;
    }
}
