<?php

namespace App\Controller\Admin;

use App\Controller\Admin\Field\VichImageField;
use App\Entity\People;
use App\Form\PeopleArchiveType;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Validator\Constraints\File;
use Vich\UploaderBundle\Form\Type\VichImageType;

class PeopleController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return People::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return parent::configureCrud($crud)
            ->setEntityLabelInPlural('Герои')
            ->setEntityLabelInSingular('запись')
            ->setPageTitle(Crud::PAGE_NEW, 'Добавление записи')
            ->setPageTitle(Crud::PAGE_EDIT, 'Изменение записи');
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')
            ->onlyOnDetail()
            ->onlyOnIndex();
        yield TextField::new('fullName', 'ФИО')->onlyOnIndex();
        yield TextField::new('fullName', 'ФИО')->onlyOnDetail();

        yield TextField::new('surname', 'Фамилия')->onlyOnForms();
        yield TextField::new('name', 'Имя')->onlyOnForms();
        yield TextField::new('patronymic', 'Отчество')->onlyOnForms();

        yield VichImageField::new('image', 'изображение')->onlyOnIndex();
        yield TextField::new('imageFile', 'Загрузить изображение')
            ->setFormType(VichImageType::class)
            ->setFormTypeOptions([
                'allow_delete' => false,
                'download_uri' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '20M',
                        'mimeTypes' => [
                            'image/jpg',
                            'image/jpeg',
                            'image/png',
                            'image/webp',
                        ],
                        'mimeTypesMessage' => 'Пожалуйста, загрузите файл в формате JPG, JPEG, PNG или WEBP.',
                        'maxSizeMessage' => 'Файл слишком большой (максимум {{ limit }}).',
                    ]),
                ],
            ])
            ->setHelp(
                '<div class="mt-3">
                    <span class="badge badge-info">*.jpg</span>
                    <span class="badge badge-info">*.jpeg</span>
                    <span class="badge badge-info">*.png</span>
                    <span class="badge badge-info">*.webp</span>
                </div>'
            )
            ->onlyOnForms();

        yield AssociationField::new('heroAwards', 'Награды');
        yield AssociationField::new('militaryRank', 'Звание');
        yield DateField::new('birthDateAt', 'Дата рождения');
        yield TextField::new('city', 'Место рождения')->onlyOnForms();
        yield DateField::new('deathDateAt', 'Дата смерти')->onlyOnForms();
        yield TextareaField::new('additional', 'Дополнительные сведения')->onlyOnForms();

        yield CollectionField::new('archive', 'Архив')
            ->setEntryType(PeopleArchiveType::class)
            ->allowAdd()
            ->allowDelete()
            ->onlyOnForms();
    }
}