<?php

namespace App\Controller\Admin;

use App\Entity\HeroAward;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class HeroAwardCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return HeroAward::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return parent::configureCrud($crud)
            ->setEntityLabelInPlural('Награды героев')
            ->setEntityLabelInSingular('награду')
            ->setPageTitle(Crud::PAGE_NEW, 'Добавление награды')
            ->setPageTitle(Crud::PAGE_EDIT, 'Изменение награды');
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')->onlyOnIndex();
        yield TextField::new('title', 'Название')
            ->setColumns(8);
        yield TextField::new('description', 'Описание');
        yield IntegerField::new('yearAt', 'Год награждения');
    }
}