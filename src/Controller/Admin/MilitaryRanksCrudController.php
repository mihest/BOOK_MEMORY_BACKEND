<?php

namespace App\Controller\Admin;

use App\Entity\MilitaryRanks;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class MilitaryRanksCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return MilitaryRanks::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return parent::configureCrud($crud)
            ->setEntityLabelInPlural('Воинские звания')
            ->setEntityLabelInSingular('звание')
            ->setPageTitle(Crud::PAGE_NEW, 'Добавление звания')
            ->setPageTitle(Crud::PAGE_EDIT, 'Изменение звания');
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')
            ->onlyOnDetail()
            ->onlyOnIndex();
        yield TextField::new('title', 'Название')->setRequired(true)
            ->setColumns(8);
    }
}