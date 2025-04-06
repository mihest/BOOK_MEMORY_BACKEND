<?php

namespace App\Controller\Admin;

use App\Entity\ApplicationFormHeroAward;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class ApplicationFormHeroAwardCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ApplicationFormHeroAward::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('title', 'Награда')
            ->setColumns(8);
        yield TextField::new('yearAt', 'Год награды')
            ->setColumns(8);
        yield TextField::new('description', 'Описание')
            ->setColumns(8);
    }
}
