<?php

namespace App\Controller\Admin;

use App\Controller\Admin\Field\VichImageField;
use App\Entity\ApplicationFormImages;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class ApplicationFormImagesCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ApplicationFormImages::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield VichImageField::new('imageFile', 'Изображение')
            ->setColumns(8);
    }
}
