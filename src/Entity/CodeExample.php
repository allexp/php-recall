<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/** Хранит пример исходного кода материала или его раздела. */
#[ORM\Entity]
#[ORM\Table(
    name: 'code_examples',
    indexes: [
        new ORM\Index(name: 'idx_code_examples_material', columns: ['material_id', 'position']),
        new ORM\Index(name: 'idx_code_examples_section', columns: ['section_id', 'position']),
    ],
)]
class CodeExample
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Material::class, inversedBy: 'codeExamples')]
    #[ORM\JoinColumn(name: 'material_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Material $material = null;

    #[ORM\ManyToOne(targetEntity: MaterialSection::class, inversedBy: 'codeExamples')]
    #[ORM\JoinColumn(name: 'section_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    private ?MaterialSection $section = null;

    #[ORM\Column(type: Types::TEXT, options: ['default' => ''])]
    private string $title = '';

    #[ORM\Column(type: Types::TEXT)]
    private string $language = '';

    #[ORM\Column(type: Types::TEXT)]
    private string $code = '';

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
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

    public function __toString(): string
    {
        return $this->title !== '' ? $this->title : $this->language;
    }
}
