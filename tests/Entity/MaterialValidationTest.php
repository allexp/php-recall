<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\Category;
use App\Entity\CodeExample;
use App\Entity\Material;
use App\Entity\MaterialSection;
use App\Entity\MaterialType;
use App\Kernel;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/** Проверяет совместимость правил валидации с существующими материалами. */
final class MaterialValidationTest extends KernelTestCase
{
    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    public function testExistingMaterialDataIsValid(): void
    {
        self::bootKernel();
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $validator = static::getContainer()->get(ValidatorInterface::class);

        foreach ([MaterialType::class, Category::class, Material::class, MaterialSection::class, CodeExample::class] as $entityClass) {
            foreach ($entityManager->getRepository($entityClass)->findAll() as $entity) {
                $violations = $validator->validate($entity);

                self::assertCount(
                    0,
                    $violations,
                    sprintf('Объект %s с ID %s не прошёл валидацию: %s', $entityClass, $entity->getId(), (string) $violations),
                );
            }
        }
    }
}
