<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\CodeExample;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CodeEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

/** Настраивает управление примерами исходного кода. */
final class CodeExampleCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return CodeExample::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Пример кода')
            ->setEntityLabelInPlural('Примеры кода')
            ->setSearchFields(['title', 'language', 'code', 'material.title', 'section.title'])
            ->setDefaultSort(['position' => 'ASC', 'id' => 'ASC'])
            ->setPaginatorPageSize(30);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')->hideOnForm();
        yield AssociationField::new('material', 'Материал')
            ->setCrudController(MaterialCrudController::class)
            ->setSortProperty('title')
            ->autocomplete();
        yield AssociationField::new('section', 'Раздел')
            ->setCrudController(MaterialSectionCrudController::class)
            ->setSortProperty('title')
            ->autocomplete()
            ->setRequired(false);
        yield TextField::new('title', 'Название')
            ->setRequired(false)
            ->setColumns(8);
        yield TextField::new('language', 'Язык')->setColumns(4);
        yield CodeEditorField::new('code', 'Исходный код')
            ->setLanguage('php')
            ->setNumOfRows(24)
            ->hideOnIndex();
        yield IntegerField::new('position', 'Позиция')->setColumns(2);
    }
}
