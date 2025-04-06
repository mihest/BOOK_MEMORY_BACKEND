<?php

namespace App\Controller\Admin;

use App\Controller\Admin\Field\VichFileField;
use App\Entity\ApplicationFormArchive;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class ApplicationFormArchiveCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ApplicationFormArchive::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield VichFileField::new('mediaFile', 'Медиа файл')
            ->setColumns(8);
    }
}
