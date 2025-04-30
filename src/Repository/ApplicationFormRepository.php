<?php

namespace App\Repository;

use App\Entity\ApplicationForm;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ApplicationForm>
 */
class ApplicationFormRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ApplicationForm::class);
    }

    public function save(ApplicationForm $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByFilters(
        ?string $city,
        ?array $letters,
        ?string $militaryRank,
        ?string $name,
        ?string $category,
        int     $page,
        int     $itemsPerPage
    ): Paginator
    {
        $qb = $this->createQueryBuilder('m');

        if ($city !== null) {
            $qb->andWhere('LOWER(m.city) LIKE :city')
                ->setParameter('city', '%' . mb_strtolower($city, 'UTF-8') . '%');
        }
        if ($letters !== null && count($letters) > 0) {
            $qb->andWhere('UPPER(SUBSTRING(m.surname, 1, 1)) IN (:letters)')
                ->setParameter('letters', $letters);
        }
        if ($militaryRank !== null) {
            $qb->andWhere('LOWER(m.militaryRank) LIKE :rank')
                ->setParameter('rank', '%' . mb_strtolower($militaryRank, 'UTF-8') . '%');
        }
        if ($name !== null) {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like('LOWER(m.surname)', ':name'),
                    $qb->expr()->like('LOWER(m.name)', ':name'),
                    $qb->expr()->like('LOWER(m.patronymic)', ':name'),
                )
            )->setParameter('name', '%' . mb_strtolower($name, 'UTF-8') . '%');
        }
        if ($category !== null) {
            $qb->andWhere('LOWER(m.category) LIKE :category')
                ->setParameter('category', '%' . mb_strtolower($category, 'UTF-8') . '%');
        }

        $qb->orderBy('m.createdAt', 'DESC')
            ->andWhere('m.status = :status')
            ->setParameter('status', 'Принята')
            ->setFirstResult(($page - 1) * $itemsPerPage)
            ->setMaxResults($itemsPerPage);

        return new Paginator($qb, true);
    }

    /**
     * Пагинированный findAll
     */
    public function findAllPaginated(int $page, int $itemsPerPage): Paginator
    {
        $qb = $this->createQueryBuilder('m')
            ->orderBy('m.createdAt', 'DESC')
            ->setFirstResult(($page - 1) * $itemsPerPage)
            ->setMaxResults($itemsPerPage)
            ->andWhere('m.status = :status')
            ->setParameter('status', 'Принята');

        return new Paginator($qb, true);
    }

//    /**
//     * @return ApplicationForm[] Returns an array of ApplicationForm objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('a')
//            ->andWhere('a.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('a.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?ApplicationForm
//    {
//        return $this->createQueryBuilder('a')
//            ->andWhere('a.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
