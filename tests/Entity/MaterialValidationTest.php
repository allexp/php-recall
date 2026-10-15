<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\Category;
use App\Entity\CodeExample;
use App\Entity\Material;
use App\Entity\MaterialSection;
use App\Entity\MaterialType;
use App\Kernel;
use App\Tests\Support\ResetsDatabase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/** Проверяет совместимость правил валидации с существующими материалами. */
final class MaterialValidationTest extends KernelTestCase
{
    use ResetsDatabase;

    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    public function testExistingMaterialDataIsValid(): void
    {
        self::bootKernel();
        $entityManager = $this->resetDatabase();
        $validator = static::getContainer()->get(ValidatorInterface::class);

        $type = (new MaterialType())->setCode('guide')->setTitle('Руководства');
        $category = (new Category())->setType($type)->setCode('testing')->setTitle('Тестирование');
        $material = (new Material())
            ->setType($type)
            ->setCategory($category)
            ->setTitle('Тестовый материал')
            ->setSlug('test-material');
        $section = (new MaterialSection())
            ->setMaterial($material)
            ->setTitle('Первый раздел')
            ->setSlug('first-section');
        $example = (new CodeExample())
            ->setMaterial($material)
            ->setSection($section)
            ->setLanguage('php')
            ->setCode('<?php echo true;');

        foreach ([$type, $category, $material, $section, $example] as $entity) {
            $entityManager->persist($entity);
        }
        $entityManager->flush();

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
