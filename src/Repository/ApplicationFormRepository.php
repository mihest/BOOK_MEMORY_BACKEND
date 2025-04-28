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
        ?int    $dayStartAt,
        ?string $monthStartAt,
        ?int    $yearStartAt,
        ?int    $dayEndAt,
        ?string $monthEndAt,
        ?int    $yearEndAt,
        ?string $city,
        ?string $letter,
        ?string $militaryRank,
        ?string $name,
        ?string $category,
        int     $page,
        int     $itemsPerPage
    ): Paginator
    {
        // 1) Базовый QB для выборки id + дат
        $baseQb = $this->createQueryBuilder('m')
            ->select('m.id, m.birthDateAt, m.deathDateAt')
            ->andWhere('m.status = :status')
            ->setParameter('status', 'Принята');

        // остальные нефильтрующие даты параметры
        if ($city !== null) {
            $baseQb->andWhere('LOWER(m.city) LIKE :city')
                ->setParameter('city', '%'.mb_strtolower($city, 'UTF-8').'%');
        }
        if ($letter !== null) {
            $baseQb->andWhere("UPPER(SUBSTRING(m.surname,1,1)) = :letter")
                ->setParameter('letter', mb_strtoupper($letter, 'UTF-8'));
        }
        if ($militaryRank !== null) {
            $baseQb->andWhere('LOWER(m.militaryRank) LIKE :rank')
                ->setParameter('rank', '%'.mb_strtolower($militaryRank, 'UTF-8').'%');
        }
        if ($name !== null) {
            $baseQb->andWhere(
                $baseQb->expr()->orX(
                    $baseQb->expr()->like('m.surname', ':name'),
                    $baseQb->expr()->like('m.name', ':name'),
                    $baseQb->expr()->like('m.patronymic', ':name'),
                    $baseQb->expr()->like('m.category', ':name')
                )
            )->setParameter('name', '%'.$name.'%');
        }
        if ($category !== null) {
            $baseQb->andWhere('LOWER(m.category) LIKE :category')
                ->setParameter('category', mb_strtolower($category, 'UTF-8'));
        }

        $baseQb->orderBy('m.createdAt', 'DESC');
        $baseRows = $baseQb->getQuery()->getArrayResult();

        // 2) Фильтрация по дате в PHP — собираем отобранные id
        $filteredIds = [];
        foreach ($baseRows as $row) {
            $b = $this->parseDateString($row['birthDateAt']);
            $d = $this->parseDateString($row['deathDateAt']);
            if (
                $this->isDateInRange($b, $dayStartAt, $monthStartAt, $yearStartAt, true) &&
                $this->isDateInRange($d, $dayEndAt,   $monthEndAt,   $yearEndAt,   false)
            ) {
                $filteredIds[] = $row['id'];
            }
        }

        // 3) Второй QB — основная выборка с пагинацией
        $qb = $this->createQueryBuilder('m')
            ->andWhere('m.status = :status')
            ->setParameter('status', 'Принята');

        // те же нефильтрующие даты параметры
        if ($city !== null) {
            $qb->andWhere('LOWER(m.city) LIKE :city')
                ->setParameter('city', '%'.mb_strtolower($city, 'UTF-8').'%');
        }
        if ($letter !== null) {
            $qb->andWhere("UPPER(SUBSTRING(m.surname,1,1)) = :letter")
                ->setParameter('letter', mb_strtoupper($letter, 'UTF-8'));
        }
        if ($militaryRank !== null) {
            $qb->andWhere('LOWER(m.militaryRank) LIKE :rank')
                ->setParameter('rank', '%'.mb_strtolower($militaryRank, 'UTF-8').'%');
        }
        if ($name !== null) {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like('m.surname', ':name'),
                    $qb->expr()->like('m.name', ':name'),
                    $qb->expr()->like('m.patronymic', ':name'),
                    $qb->expr()->like('m.category', ':name')
                )
            )->setParameter('name', '%'.$name.'%');
        }
        if ($category !== null) {
            $qb->andWhere('LOWER(m.category) LIKE :category')
                ->setParameter('category', mb_strtolower($category, 'UTF-8'));
        }

        // если ничего не отфильтровалось — пустой результат
        if (empty($filteredIds)) {
            $qb->andWhere('1 = 0');
        } else {
            $qb->andWhere('m.id IN (:ids)')
                ->setParameter('ids', $filteredIds);
        }

        // пагинация и сортировка — как было
        $qb->orderBy('m.createdAt', 'DESC')
            ->setFirstResult(($page - 1) * $itemsPerPage)
            ->setMaxResults($itemsPerPage);

        return new Paginator($qb, true);
    }

    /**
     * Парсит строку вида "1 Январь 2025" или "Январь 2025" или "2025"
     * в ['day'=>int|null,'month'=>int|null,'year'=>int|null]
     */
    private function parseDateString(?string $s): array {
        if (!$s) {
            return ['day'=>null,'month'=>null,'year'=>null];
        }
        $parts = preg_split('/\s+/', trim($s));
        $months = [
            'Январь'=>1,'Февраль'=>2,'Март'=>3,'Апрель'=>4,'Май'=>5,'Июнь'=>6,
            'Июль'=>7,'Август'=>8,'Сентябрь'=>9,'Октябрь'=>10,'Ноябрь'=>11,'Декабрь'=>12,
        ];
        $day = null; $month = null; $year = null;
        foreach ($parts as $p) {
            if (is_numeric($p) && (int)$p > 31)        $year  = (int)$p;
            elseif (isset($months[$p]))               $month = $months[$p];
            elseif (is_numeric($p) && (int)$p <= 31)   $day   = (int)$p;
        }
        return ['day'=>$day,'month'=>$month,'year'=>$year];
    }

    /**
     * Проверяет, попадает ли дата в диапазон.
     * Если $isStart=true — фильтр "не раньше", иначе — "не позже".
     */
    private function isDateInRange(array $date, $day, $month, $year, bool $isStart): bool {
        // если нет ни одного фильтра — пропускаем
        if ($year===null && $month===null && $day===null) {
            return true;
        }
        // если в строке нет года — считаем не попавшим (можно поменять на true, если нужно)
        if ($date['year']===null) {
            return false;
        }

        $cmpY = $date['year'];
        $cmpM = $date['month'] ?? ($isStart ? 1 : 12);
        $cmpD = $date['day']   ?? ($isStart ? 1 : 31);

        $fY = $year  ?? ($isStart ? 0 : 9999);
        $fM = $month ? $this->monthToNumber($month) : ($isStart ? 1 : 12);
        $fD = $day   ?? ($isStart ? 1 : 31);

        $valDate   = $cmpY * 10000 + $cmpM * 100 + $cmpD;
        $valFilter = $fY   * 10000 + $fM   * 100 + $fD;

        return $isStart
            ? ($valDate   >= $valFilter)
            : ($valDate   <= $valFilter);
    }

    /** Название месяца → номер */
    private function monthToNumber(?string $m): ?int {
        if (!$m) return null;
        $map = [
            'Январь'=>1,'Февраль'=>2,'Март'=>3,'Апрель'=>4,'Май'=>5,'Июнь'=>6,
            'Июль'=>7,'Август'=>8,'Сентябрь'=>9,'Октябрь'=>10,'Ноябрь'=>11,'Декабрь'=>12,
        ];
        return $map[$m] ?? null;
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
