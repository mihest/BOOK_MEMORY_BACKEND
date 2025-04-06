<?php

namespace App\Controller\Admin;

use App\Entity\ApplicationForm;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class ApplicationFormCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ApplicationForm::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return parent::configureCrud($crud)
            ->setEntityLabelInPlural('Заявки с форм')
            ->setEntityLabelInSingular('заявку')
            ->setPageTitle(Crud::PAGE_NEW, 'Добавление заявки')
            ->setPageTitle(Crud::PAGE_EDIT, 'Изменение заявки');
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('surname', 'Фамилия')
            ->setColumns(8);
        yield TextField::new('name', 'Имя')
            ->setColumns(8);
        yield TextField::new('patronymic', 'Отчество')
            ->setColumns(8);
        yield TextField::new('city', 'Место рождения')
            ->setColumns(8);
        yield TextField::new('category', 'Категория героя')
            ->setColumns(8);
        yield TextField::new('militaryRank', 'Воинское звание')
            ->setColumns(8);
        yield TextField::new('birthDateAt', 'Дата рождения')
            ->setColumns(8);
        yield TextField::new('deathDateAt', 'Дата смерти')
            ->setColumns(8);
        yield TextEditorField::new('additional', 'Дополнительные сведения')
            ->setColumns(8);
        yield TextField::new('surnameSender', 'Фамилия отправителя')
            ->onlyOnForms()
            ->setColumns(8);
        yield TextField::new('nameSender', 'Имя отправителя')
            ->onlyOnForms()
            ->setColumns(8);
        yield TextField::new('patronymicSender', 'Отчество отправителя')
            ->onlyOnForms()
            ->setColumns(8);
        yield TextField::new('phone', 'Номер телефона отправителя')
            ->onlyOnForms()
            ->setColumns(8);
        yield TextEditorField::new('sender', 'Получатель')
            ->onlyOnIndex();

        yield CollectionField::new('images', 'Избражения')
            ->setRequired(false)
            ->showEntryLabel(false)
            ->useEntryCrudForm(ApplicationFormImagesCrudController::class)
            ->setColumns(8)
            ->hideOnIndex();

        yield CollectionField::new('archive', 'Архив')
            ->setRequired(false)
            ->showEntryLabel(false)
            ->useEntryCrudForm(ApplicationFormArchiveCrudController::class)
            ->setColumns(8)
            ->hideOnIndex();

        yield CollectionField::new('heroAward', 'Награды героя')
            ->setRequired(false)
            ->showEntryLabel(false)
            ->useEntryCrudForm(ApplicationFormHeroAwardCrudController::class)
            ->setColumns(8)
            ->hideOnIndex();

        yield TextEditorField::new('heroAwardAll', 'Награды героя')
            ->onlyOnIndex();
    }
}