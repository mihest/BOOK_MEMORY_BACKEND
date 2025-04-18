<?php

namespace App\Controller\Admin;

use App\Entity\AiKeywordBanned;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class AiKeywordBannedCrudController extends AbstractCrudController
{

    public static function getEntityFqcn(): string
    {
        return AiKeywordBanned::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return parent::configureCrud($crud)
            ->setEntityLabelInPlural('Слова забенные нейросетью')
            ->setEntityLabelInSingular('слово')
            ->setPageTitle(Crud::PAGE_NEW, 'Добавление слова')
            ->setPageTitle(Crud::PAGE_EDIT, 'Изменение слова');
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')
            ->hideOnForm();
        yield TextField::new('title', 'Наименование')
            ->setColumns(8);
    }
}
