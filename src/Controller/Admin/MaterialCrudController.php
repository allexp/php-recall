<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Category;
use App\Entity\Material;
use EasyCorp\Bundle\EasyAdminBundle\Config\Asset;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;

/** Настраивает управление учебными материалами. */
final class MaterialCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Material::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Материал')
            ->setEntityLabelInPlural('Материалы')
            ->setSearchFields(['title', 'slug', 'definition', 'shortDescription', 'fullDescription', 'type.title', 'category.title'])
            ->setDefaultSort(['position' => 'ASC', 'title' => 'ASC'])
            ->setPaginatorPageSize(30);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')->hideOnForm();
        yield AssociationField::new('type', 'Тип')
            ->setCrudController(MaterialTypeCrudController::class)
            ->setSortProperty('title')
            ->autocomplete()
            ->setFormTypeOption('attr.data-material-type-source', 'true')
            ->setColumns(6);
        yield AssociationField::new('category', 'Категория')
            ->setCrudController(CategoryCrudController::class)
            ->setSortProperty('title')
            ->setFormTypeOption('choice_attr', static fn (Category $category): array => [
                'data-material-type-id' => (string) $category->getType()?->getId(),
            ])
            ->setFormTypeOption('attr.data-material-category-target', 'true')
            ->setRequired(false)
            ->addJsFiles(Asset::new('admin-material-category-filter.js')->onlyOnForms())
            ->setColumns(6);
        yield TextField::new('title', 'Название')->setColumns(8);
        yield SlugField::new('slug', 'Slug')
            ->setTargetFieldName('title')
            ->setUnlockConfirmationMessage('Изменение slug может нарушить существующие ссылки. Продолжить?')
            ->setColumns(4);
        yield TextEditorField::new('definition', 'Определение')
            ->setNumOfRows(8)
            ->hideOnIndex();
        yield TextareaField::new('shortDescription', 'Краткое описание')
            ->setNumOfRows(6)
            ->hideOnIndex();
        yield TextEditorField::new('fullDescription', 'Полное описание')
            ->setNumOfRows(18)
            ->hideOnIndex();
        yield ChoiceField::new('contentFormat', 'Формат')
            ->setChoices([
                'HTML' => Material::FORMAT_HTML,
                'Markdown' => Material::FORMAT_MARKDOWN,
                'Текст' => Material::FORMAT_TEXT,
            ])
            ->setColumns(4);
        yield UrlField::new('sourceUrl', 'Источник')
            ->setRequired(false)
            ->setColumns(6)
            ->hideOnIndex();
        yield IntegerField::new('position', 'Позиция')->setColumns(2);
        yield TextField::new('createdAt', 'Создан')->hideOnForm();
        yield TextField::new('updatedAt', 'Изменён')->hideOnForm();
    }
}
