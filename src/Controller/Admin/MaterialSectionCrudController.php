<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\MaterialSection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

/** Настраивает управление разделами учебных материалов. */
final class MaterialSectionCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return MaterialSection::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Раздел материала')
            ->setEntityLabelInPlural('Разделы материалов')
            ->setSearchFields(['title', 'slug', 'description', 'material.title'])
            ->setDefaultSort(['position' => 'ASC', 'title' => 'ASC'])
            ->setPaginatorPageSize(30);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')->hideOnForm();
        yield AssociationField::new('material', 'Материал')
            ->setCrudController(MaterialCrudController::class)
            ->setSortProperty('title')
            ->autocomplete();
        yield TextField::new('title', 'Название')->setColumns(8);
        yield SlugField::new('slug', 'Slug')
            ->setTargetFieldName('title')
            ->setUnlockConfirmationMessage('Изменение slug может нарушить существующие ссылки. Продолжить?')
            ->setColumns(4);
        yield TextEditorField::new('description', 'Описание')
            ->setNumOfRows(14)
            ->hideOnIndex();
        yield IntegerField::new('position', 'Позиция')->setColumns(2);
    }
}
