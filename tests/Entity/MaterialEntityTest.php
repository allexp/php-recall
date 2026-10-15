<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\Category;
use App\Entity\CodeExample;
use App\Entity\Material;
use App\Entity\MaterialSection;
use App\Entity\MaterialType;
use PHPUnit\Framework\TestCase;

/** Проверяет согласованность связей сущностей материалов. */
final class MaterialEntityTest extends TestCase
{
    public function testBuildsCompleteMaterialGraph(): void
    {
        $type = (new MaterialType())->setCode('concept')->setTitle('Концепции');
        $category = (new Category())->setCode('principles')->setTitle('Принципы');
        $material = (new Material())->setTitle('SOLID')->setSlug('solid');
        $section = (new MaterialSection())->setTitle('SRP')->setSlug('srp');
        $example = (new CodeExample())->setLanguage('php')->setCode('final class Service {}');

        $type->addCategory($category);
        $type->addMaterial($material);
        $category->addMaterial($material);
        $material->addSection($section);
        $section->addCodeExample($example);

        self::assertSame($type, $category->getType());
        self::assertSame($type, $material->getType());
        self::assertSame($category, $material->getCategory());
        self::assertSame($material, $section->getMaterial());
        self::assertSame($material, $example->getMaterial());
        self::assertSame($section, $example->getSection());
        self::assertTrue($section->getCodeExamples()->contains($example));
    }

    public function testInitialisesMaterialTimestamps(): void
    {
        $material = new Material();

        self::assertNotFalse(\DateTimeImmutable::createFromFormat(DATE_ATOM, $material->getCreatedAt()));
        self::assertSame($material->getCreatedAt(), $material->getUpdatedAt());
    }
}
