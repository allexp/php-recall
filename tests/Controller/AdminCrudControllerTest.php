<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\AdminUser;
use App\Entity\Category;
use App\Entity\CodeExample;
use App\Entity\Material;
use App\Entity\MaterialSection;
use App\Entity\MaterialType;
use App\Kernel;
use App\Tests\Support\ResetsDatabase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/** Проверяет полный цикл управления материалом через EasyAdmin. */
final class AdminCrudControllerTest extends WebTestCase
{
    use ResetsDatabase;

    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    public function testCreatesEditsValidatesAndDeletesMaterialGraph(): void
    {
        $client = static::createClient();
        $entityManager = $this->resetDatabase();
        $admin = (new AdminUser())
            ->setEmail('crud-test@example.com')
            ->setPassword('unused-test-hash');
        $entityManager->persist($admin);
        $entityManager->flush();
        $client->loginUser($admin, 'admin');

        $this->assertCodeIsGeneratedFromTitle($client, '/admin/material-type/new', 'MaterialType');
        $this->submitForm($client, '/admin/material-type/new', 'MaterialType', [
            'title' => 'Руководства',
            'code' => 'guide',
        ]);
        $type = $this->repository(MaterialType::class)->findOneBy(['code' => 'guide']);
        self::assertInstanceOf(MaterialType::class, $type);

        $crawler = $client->request('GET', '/admin/category/new');
        $typeOption = $crawler->filter(sprintf(
            'select[name="Category[type]"] option[value="%d"]',
            $type->getId(),
        ));
        self::assertCount(1, $typeOption);
        self::assertSame('Руководства', trim($typeOption->text()));

        $crawler = $client->request('GET', '/admin/material-type/new');
        $form = $crawler->filter('form[name="MaterialType"]')->form();
        $form->setValues([
            'MaterialType[title]' => 'Дубликат типа',
            'MaterialType[code]' => 'guide',
        ]);
        $client->submit($form);
        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('.invalid-feedback', 'Тип материала с таким кодом уже существует.');
        self::assertCount(1, $this->repository(MaterialType::class)->findBy(['code' => 'guide']));

        $this->assertCodeIsGeneratedFromTitle($client, '/admin/category/new', 'Category');
        $this->submitForm($client, '/admin/category/new', 'Category', [
            'type' => (string) $type->getId(),
            'title' => 'Тестирование',
            'code' => 'testing',
            'position' => '10',
        ]);
        $category = $this->repository(Category::class)->findOneBy(['code' => 'testing']);
        self::assertInstanceOf(Category::class, $category);

        $this->submitForm($client, '/admin/material/new', 'Material', [
            'type' => (string) $type->getId(),
            'category' => (string) $category->getId(),
            'title' => 'Интеграционное тестирование',
            'slug' => 'integration-testing',
            'definition' => '<p>Первоначальное определение.</p>',
            'shortDescription' => 'Краткое описание.',
            'fullDescription' => '<p>Полное описание.</p>',
            'contentFormat' => Material::FORMAT_HTML,
            'sourceUrl' => 'https://example.com/testing',
            'position' => '20',
        ]);
        $material = $this->repository(Material::class)->findOneBy(['slug' => 'integration-testing']);
        self::assertInstanceOf(Material::class, $material);
        self::assertSame($type->getId(), $material->getType()?->getId());
        self::assertSame($category->getId(), $material->getCategory()?->getId());

        $this->submitForm($client, '/admin/material-section/new', 'MaterialSection', [
            'material' => (string) $material->getId(),
            'title' => 'Подготовка',
            'slug' => 'setup',
            'description' => '<p>Настройка окружения.</p>',
            'position' => '0',
        ]);
        $section = $this->repository(MaterialSection::class)->findOneBy(['slug' => 'setup']);
        self::assertInstanceOf(MaterialSection::class, $section);

        $this->submitForm($client, '/admin/code-example/new', 'CodeExample', [
            'material' => (string) $material->getId(),
            'section' => (string) $section->getId(),
            'title' => 'Пример теста',
            'language' => 'php',
            'code' => '<?php self::assertTrue(true);',
            'position' => '0',
        ]);
        $example = $this->repository(CodeExample::class)->findOneBy(['title' => 'Пример теста']);
        self::assertInstanceOf(CodeExample::class, $example);
        self::assertSame($section->getId(), $example->getSection()?->getId());

        $entityManager = $this->entityManager();
        $material = $entityManager->find(Material::class, $material->getId());
        self::assertInstanceOf(Material::class, $material);
        $material->setUpdatedAt('2000-01-01T00:00:00+00:00');
        $entityManager->flush();

        $this->submitForm($client, sprintf('/admin/material/%d/edit', $material->getId()), 'Material', [
            'title' => 'Обновлённое интеграционное тестирование',
        ]);
        $material = $this->repository(Material::class)->find($material->getId());
        self::assertInstanceOf(Material::class, $material);
        self::assertSame('Обновлённое интеграционное тестирование', $material->getTitle());
        self::assertNotSame('2000-01-01T00:00:00+00:00', $material->getUpdatedAt());

        $otherType = (new MaterialType())->setCode('other')->setTitle('Другой тип');
        $otherCategory = (new Category())->setType($otherType)->setCode('other')->setTitle('Другая категория');
        $entityManager = $this->entityManager();
        $entityManager->persist($otherType);
        $entityManager->persist($otherCategory);
        $entityManager->flush();

        $crawler = $client->request('GET', '/admin/material/new');
        self::assertSelectorExists('script[src$="/admin-material-category-filter.js"]');
        $form = $crawler->filter('form[name="Material"]')->form();
        self::assertSame(
            (string) $type->getId(),
            $crawler->filter(sprintf('select[name="Material[category]"] option[value="%d"]', $category->getId()))->attr('data-material-type-id'),
        );
        self::assertSame(
            (string) $otherType->getId(),
            $crawler->filter(sprintf('select[name="Material[category]"] option[value="%d"]', $otherCategory->getId()))->attr('data-material-type-id'),
        );
        $form['Material[type][autocomplete]']->disableValidation()->select((string) $type->getId());
        $form['Material[category]']->disableValidation()->select((string) $otherCategory->getId());
        $form->setValues([
            'Material[title]' => 'Материал с неверной категорией',
            'Material[slug]' => 'invalid-category',
            'Material[definition]' => 'Определение',
            'Material[shortDescription]' => 'Описание',
            'Material[fullDescription]' => 'Полное описание',
            'Material[contentFormat]' => Material::FORMAT_TEXT,
            'Material[position]' => '0',
        ]);
        $client->submit($form);
        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('.invalid-feedback', 'Категория должна принадлежать выбранному типу материала.');
        self::assertNull($this->repository(Material::class)->findOneBy(['slug' => 'invalid-category']));

        $materialId = $material->getId();
        self::assertNotNull($materialId);
        $client->request('POST', sprintf('/admin/material/%d/delete', $materialId), ['token' => 'invalid']);
        self::assertNotNull($this->repository(Material::class)->find($materialId));

        $crawler = $client->request('GET', '/admin/material');
        $csrfToken = (string) $crawler->filter('#action-confirmation-form input[name="token"]')->attr('value');
        $client->request('POST', sprintf('/admin/material/%d/delete', $materialId), ['token' => $csrfToken]);
        self::assertResponseRedirects('/admin/material');
        self::assertNull($this->repository(Material::class)->find($materialId));
        self::assertNull($this->repository(MaterialSection::class)->find($section->getId()));
        self::assertNull($this->repository(CodeExample::class)->find($example->getId()));
    }

    private function assertCodeIsGeneratedFromTitle(KernelBrowser $client, string $path, string $formName): void
    {
        $crawler = $client->request('GET', $path);

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('script[src*="field-slug"]');
        self::assertSame(
            json_encode([sprintf('%s_title', $formName)], JSON_THROW_ON_ERROR),
            $crawler->filter(sprintf('[name="%s[code]"]', $formName))->attr('data-target'),
        );
    }

    /** @param array<string, string> $values */
    private function submitForm(KernelBrowser $client, string $path, string $formName, array $values): void
    {
        $crawler = $client->request('GET', $path);
        self::assertResponseIsSuccessful();
        $form = $crawler->filter(sprintf('form[name="%s"]', $formName))->form();

        foreach ($values as $field => $value) {
            $fieldName = sprintf('%s[%s]', $formName, $field);
            $autocompleteFieldName = sprintf('%s[%s][autocomplete]', $formName, $field);
            if ($crawler->filter(sprintf('[name="%s"]', $autocompleteFieldName))->count() > 0) {
                $form[$autocompleteFieldName]->disableValidation()->select($value);
                continue;
            }

            $form[$fieldName] = $value;
        }

        $client->submit($form);
        self::assertResponseRedirects();
    }

    private function entityManager(): EntityManagerInterface
    {
        return static::getContainer()->get(EntityManagerInterface::class);
    }

    /** @param class-string $entityClass */
    private function repository(string $entityClass): \Doctrine\Persistence\ObjectRepository
    {
        return $this->entityManager()->getRepository($entityClass);
    }
}
