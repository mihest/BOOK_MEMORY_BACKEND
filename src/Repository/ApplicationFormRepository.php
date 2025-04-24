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
    private const MONTH_MAP = [
        'Январь'   => 1, 'Февраль'  => 2, 'Март'     => 3, 'Апрель'  => 4,
        'Май'      => 5, 'Июнь'     => 6, 'Июль'     => 7, 'Август'  => 8,
        'Сентябрь' => 9, 'Октябрь'  => 10,'Ноябрь'   => 11,'Декабрь' => 12,
    ];

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
        ?int    $day,
        ?string $month,
        ?int    $year,
        ?string $city,
        ?string $letter,
        ?string $militaryRank,
        ?string $name,
        int     $page,
        int     $itemsPerPage
    ): Paginator
    {
        $qb = $this->createQueryBuilder('m');

        if ($day !== null) {
            $qb->andWhere(
                "SUBSTRING(m.birthDateAt, 1, LOCATE(' ', m.birthDateAt) - 1) = :day"
            )
                ->setParameter('day', (string)$day);
        }

        if ($month !== null) {
            $qb->andWhere('m.birthDateAt LIKE :month')
                ->setParameter('month', '%'.$month.'%');
        }

        if ($year !== null) {
            $qb->andWhere('(m.birthDateAt LIKE :year OR m.deathDateAt LIKE :year)')
                ->setParameter('year', '%'.$year.'%');
        }

        if ($city !== null) {
            $qb->andWhere('LOWER(m.city) LIKE :city')
                ->setParameter('city', '%'.mb_strtolower($city, 'UTF-8').'%');
        }

        if ($letter !== null) {
            $qb->andWhere("UPPER(SUBSTRING(m.surname, 1, 1)) = :letter")
                ->setParameter('letter', $letter);
        }

        if (null !== $militaryRank) {
            $qb->andWhere('LOWER(m.militaryRank) LIKE :rank')
                ->setParameter('rank', '%'.mb_strtolower($militaryRank, 'UTF-8').'%');
        }

        if ($name !== null) {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like('m.surname',    ':name'),
                    $qb->expr()->like('m.firstname',  ':name'),
                    $qb->expr()->like('m.patronymic', ':name')
                )
            )
                ->setParameter('name', '%'.$name.'%');
        }

        $qb->setFirstResult(($page - 1) * $itemsPerPage)
            ->setMaxResults($itemsPerPage)
            ->orderBy('m.surname', 'ASC');

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
            ->setMaxResults($itemsPerPage);

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
