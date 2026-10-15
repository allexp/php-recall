<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\MaterialType;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

/** Настраивает управление системными типами материалов. */
final class MaterialTypeCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return MaterialType::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Тип материала')
            ->setEntityLabelInPlural('Типы материалов')
            ->setSearchFields(['title', 'code'])
            ->setDefaultSort(['title' => 'ASC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')->hideOnForm();
        yield TextField::new('title', 'Название')->setColumns(8);
        yield TextField::new('code', 'Код')
            ->setHelp('Строчные латинские буквы, цифры и дефисы.')
            ->setColumns(4);
    }
}
