<?php

namespace App\Filters;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\PropertyInfo\Type;

class PeopleFullNameFilter extends AbstractFilter
{
    protected function filterProperty(
        string $property,
               $value,
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation = null,
        array $context = []
    ): void {
        if ($property !== 'full_name' || empty($value)) {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];
        $paramName = $queryNameGenerator->generateParameterName($property);

        // Приводим значение к нижнему регистру и убираем пробелы по краям
        $normalizedValue = strtolower(trim($value));

        // Возможные комбинации ФИО
        $combinations = [
            "LOWER(TRIM(CONCAT($alias.surname, ' ', $alias.name, ' ', COALESCE($alias.patronymic, ''))))",
            "LOWER(TRIM(CONCAT($alias.surname, ' ', COALESCE($alias.patronymic, ''), ' ', $alias.name)))",
            "LOWER(TRIM(CONCAT($alias.name, ' ', $alias.surname, ' ', COALESCE($alias.patronymic, ''))))",
            "LOWER(TRIM(CONCAT($alias.name, ' ', COALESCE($alias.patronymic, ''), ' ', $alias.surname)))",
            "LOWER(TRIM(CONCAT(COALESCE($alias.patronymic, ''), ' ', $alias.name, ' ', $alias.surname)))",
            "LOWER(TRIM(CONCAT(COALESCE($alias.patronymic, ''), ' ', $alias.surname, ' ', $alias.name)))",
        ];

        $orX = $queryBuilder->expr()->orX();
        foreach ($combinations as $expression) {
            $orX->add("$expression LIKE :$paramName");
        }

        $queryBuilder
            ->andWhere($orX)
            ->setParameter($paramName, '%' . $normalizedValue . '%');
    }

    public function getDescription(string $resourceClass): array
    {
        return [
            'full_name' => [
                'property' => 'full_name',
                'type' => Type::BUILTIN_TYPE_STRING,
                'required' => false,
                'description' => 'Поиск по полному имени (ФИО в любом порядке)',
                'openapi' => [
                    'name' => 'full_name',
                    'description' => 'Поиск по полному имени (ФИО в любом порядке)',
                ],
            ],
        ];
    }
}
