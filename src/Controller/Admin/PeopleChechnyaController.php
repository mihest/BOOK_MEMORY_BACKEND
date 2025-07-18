<?php

namespace App\Controller\Admin;

use App\Entity\People;
use App\Entity\PeopleType;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;

class PeopleChechnyaController extends PeopleController
{
    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): \Doctrine\ORM\QueryBuilder
    {
        $qb = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);
        $qb->andWhere('entity.type = :type')->setParameter('type', PeopleType::CHECHNYA);
        return $qb;
    }

    public function createEntity(string $entityFqcn): People
    {
        $people = new People();
        $people->setType(PeopleType::CHECHNYA);
        return $people;
    }
}