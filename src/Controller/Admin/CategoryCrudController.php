<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Category;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

/** Настраивает управление категориями материалов. */
final class CategoryCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Category::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Категория')
            ->setEntityLabelInPlural('Категории')
            ->setSearchFields(['title', 'code', 'type.title'])
            ->setDefaultSort(['position' => 'ASC', 'title' => 'ASC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')->hideOnForm();
        yield AssociationField::new('type', 'Тип')
            ->setCrudController(MaterialTypeCrudController::class)
            ->setSortProperty('title')
            ->setColumns(6);
        yield TextField::new('title', 'Название')->setColumns(8);
        yield TextField::new('code', 'Код')
            ->setHelp('Строчные латинские буквы, цифры и дефисы.')
            ->setColumns(4);
        yield IntegerField::new('position', 'Позиция')->setColumns(2);
    }
}
