<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/** Хранит пример исходного кода материала или его раздела. */
#[ORM\Entity]
#[ORM\Table(name: 'code_examples')]
#[ORM\Index(name: 'idx_code_examples_material', columns: ['material_id', 'position'])]
#[ORM\Index(name: 'idx_code_examples_section', columns: ['section_id', 'position'])]
class CodeExample
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Material::class, inversedBy: 'codeExamples')]
    #[ORM\JoinColumn(name: 'material_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull(message: 'Выберите материал.')]
    private ?Material $material = null;

    #[ORM\ManyToOne(targetEntity: MaterialSection::class, inversedBy: 'codeExamples')]
    #[ORM\JoinColumn(name: 'section_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    private ?MaterialSection $section = null;

    #[ORM\Column(type: Types::TEXT, options: ['default' => ''])]
    private string $title = '';

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'Укажите язык примера.')]
    private string $language = '';

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'Добавьте исходный код примера.')]
    private string $code = '';

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    #[Assert\PositiveOrZero(message: 'Позиция не может быть отрицательной.')]
    private int $position = 0;

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

        return $this;
    }

    public function getSection(): ?MaterialSection
    {
        return $this->section;
    }

    public function setSection(?MaterialSection $section): self
    {
        $this->section = $section;

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

    public function getLanguage(): string
    {
        return $this->language;
    }

    public function setLanguage(string $language): self
    {
        $this->language = $language;

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

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): self
    {
        $this->position = $position;

        return $this;
    }

    #[Assert\Callback]
    public function validateSectionMaterial(ExecutionContextInterface $context): void
    {
        if ($this->section !== null && $this->section->getMaterial() !== $this->material) {
            $context->buildViolation('Раздел должен принадлежать выбранному материалу.')
                ->atPath('section')
                ->addViolation();
        }
    }

    public function __toString(): string
    {
        return $this->title !== '' ? $this->title : $this->language;
    }
}
