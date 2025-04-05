<?php

namespace App\Controller\Admin;

use App\Entity\PersonalDataAccept;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;

class PersonalDataAcceptCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return PersonalDataAccept::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return parent::configureCrud($crud)
            ->setEntityLabelInPlural('Согласие на обработку персональных данных')
            ->setEntityLabelInSingular('данные')
            ->setPageTitle(Crud::PAGE_NEW, 'Добавление данных')
            ->setPageTitle(Crud::PAGE_EDIT, 'Изменение данных');
    }

    public function configureActions(Actions $actions): Actions
    {
        $actions->remove(Crud::PAGE_NEW, Action::SAVE_AND_ADD_ANOTHER);
        $actions->remove(Crud::PAGE_NEW, Action::SAVE_AND_RETURN);
        $actions->remove(Crud::PAGE_EDIT, Action::SAVE_AND_RETURN);
        $actions->add(Crud::PAGE_NEW, Action::SAVE_AND_CONTINUE);

        return parent::configureActions($actions);
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextEditorField::new('description', 'Название')
            ->setColumns(8);
    }
}