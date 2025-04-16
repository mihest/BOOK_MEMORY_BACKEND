<?php

namespace App\Controller\Admin;

use App\Entity\Institutions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class InstitutionsCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Institutions::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return parent::configureCrud($crud)
            ->setEntityLabelInPlural('Организации')
            ->setEntityLabelInSingular('организацию')
            ->setPageTitle(Crud::PAGE_NEW, 'Добавление организации')
            ->setPageTitle(Crud::PAGE_EDIT, 'Изменение организации');
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('title', 'Название')
            ->setColumns(8);
    }
}