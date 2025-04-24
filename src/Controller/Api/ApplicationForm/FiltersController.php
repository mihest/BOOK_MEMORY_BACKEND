<?php

namespace App\Controller\Api\ApplicationForm;

use App\Repository\ApplicationFormRepository;
use App\Repository\MilitaryRanksRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;

class FiltersController extends AbstractController
{
    private const MONTH_MAP = [
        'Январь'    => 1,
        'Февраль'   => 2,
        'Март'      => 3,
        'Апрель'    => 4,
        'Май'       => 5,
        'Июнь'      => 6,
        'Июль'      => 7,
        'Август'    => 8,
        'Сентябрь'  => 9,
        'Октябрь'   => 10,
        'Ноябрь'    => 11,
        'Декабрь'   => 12,
    ];

    public function __construct(
        private readonly ApplicationFormRepository $applicationFormRepository,
        private readonly MilitaryRanksRepository  $militaryRanksRepository,
    ) {}

    public function __invoke(): JsonResponse
    {
        $members = $this->applicationFormRepository->findAll();

        $days   = [];
        $months = [];
        $years  = [];
        $cities = [];
        $words  = [];

        foreach ($members as $member) {
            foreach ([$member->getBirthDateAt(), $member->getDeathDateAt()] as $dateStr) {
                if (!$dateStr) {
                    continue;
                }
                foreach (preg_split('/\s+/', trim($dateStr)) as $part) {
                    if (preg_match('/^\d{4}$/', $part)) {
                        $years[(int)$part] = true;
                    }
                    elseif (isset(self::MONTH_MAP[$part])) {
                        $months[self::MONTH_MAP[$part]] = true;
                    }
                    elseif (preg_match('/^\d{1,2}$/', $part)) {
                        $days[(int)$part] = true;
                    }
                }
            }

            $city = trim((string)$member->getCity());
            if ($city !== '') {
                $cities[$city] = true;
            }

            $surname = trim((string)$member->getSurname());
            if ($surname !== '' && $surname !== '-') {
                $clean = preg_replace('/[^А-Яа-яЁё]/u', '', $surname);
                if ($clean !== '') {
                    $letter = mb_strtoupper(mb_substr($clean, 0, 1, 'UTF-8'), 'UTF-8');
                    $words[$letter] = true;
                }
            }
        }

        $response = [
            'days'          => $this->getSortedList($days,   SORT_NUMERIC),
            'months'        => $this->getSortedMonthNamesList($months),
            'years'         => $this->getSortedList($years,  SORT_NUMERIC),
            'city'          => $this->getSortedList($cities, SORT_LOCALE_STRING),
            'letters'       => $this->getSortedList($words,  SORT_LOCALE_STRING),
            'militaryRanks' => $this->getMilitaryRankTitles(),
        ];

        return $this->json($response);
    }

    /**
     * Сортирует ключи массива и возвращает их в виде списка.
     */
    private function getSortedList(array $items, int $sortFlag = SORT_STRING): array
    {
        $values = array_keys($items);
        sort($values, $sortFlag);
        return $values;
    }

    /**
     * Возвращает отсортированный список названий месяцев на основе числовых ключей.
     */
    private function getSortedMonthNamesList(array $monthNumbers): array
    {
        $numbers = array_keys($monthNumbers);
        sort($numbers, SORT_NUMERIC);

        $flip = array_flip(self::MONTH_MAP);
        $names = [];

        foreach ($numbers as $num) {
            if (isset($flip[$num])) {
                $names[] = $flip[$num];
            }
        }

        return $names;
    }

    /**
     * Возвращает отсортированный список титулов военной службы.
     */
    private function getMilitaryRankTitles(): array
    {
        $titles = array_map(
            fn($rank) => trim((string)$rank->getTitle()),
            $this->militaryRanksRepository->findAll()
        );

        $titles = array_filter($titles, fn($t) => $t !== '');
        sort($titles, SORT_LOCALE_STRING);

        return $titles;
    }
}
