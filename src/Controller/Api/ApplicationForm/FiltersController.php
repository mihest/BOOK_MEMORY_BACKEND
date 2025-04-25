<?php

namespace App\Controller\Api\ApplicationForm;

use App\Repository\ApplicationFormRepository;
use App\Repository\MilitaryRanksRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;

class FiltersController extends AbstractController
{
    private const MONTH_MAP = [
        'Январь'   => 1,
        'Февраль'  => 2,
        'Март'     => 3,
        'Апрель'   => 4,
        'Май'      => 5,
        'Июнь'     => 6,
        'Июль'     => 7,
        'Август'   => 8,
        'Сентябрь' => 9,
        'Октябрь'  => 10,
        'Ноябрь'   => 11,
        'Декабрь'  => 12,
    ];

    public function __construct(
        private readonly ApplicationFormRepository $applicationFormRepository,
        private readonly MilitaryRanksRepository  $militaryRanksRepository,
    ) {}

    public function __invoke(): JsonResponse
    {
        // Получаем всех принятых участников
        $members = $this->applicationFormRepository->findBy(['status' => 'Принята']);

        // Фильтры для дат рождения/смерти
        $birthDays   = [];
        $birthMonths = [];
        $birthYears  = [];
        $deathDays   = [];
        $deathMonths = [];
        $deathYears  = [];

        $letters        = [];
        $usedRankTitles = [];

        foreach ($members as $member) {
            // Данные рождения и смерти
            foreach (['birth' => $member->getBirthDateAt(), 'death' => $member->getDeathDateAt()] as $type => $dateStr) {
                if (!$dateStr) {
                    continue;
                }

                foreach (preg_split('/\s+/', trim($dateStr)) as $part) {
                    if (preg_match('/^\d{4}$/', $part)) {
                        ${$type . 'Years'}[(int)$part] = true;
                    } elseif (isset(self::MONTH_MAP[$part])) {
                        ${$type . 'Months'}[self::MONTH_MAP[$part]] = true;
                    } elseif (preg_match('/^\d{1,2}$/', $part)) {
                        ${$type . 'Days'}[(int)$part] = true;
                    }
                }
            }

            // Первая буква фамилии
            $surname = trim((string)$member->getSurname());
            if ($surname !== '' && $surname !== '-') {
                $clean = preg_replace('/[^А-Яа-яЁё]/u', '', $surname);
                if ($clean !== '') {
                    $letter = mb_strtoupper(mb_substr($clean, 0, 1, 'UTF-8'), 'UTF-8');
                    $letters[$letter] = true;
                }
            }

            // Строковое звание
            $rankString = trim((string)$member->getMilitaryRank());
            if ($rankString !== '') {
                $usedRankTitles[$rankString] = true;
            }
        }

        // Получаем и фильтруем звания из репозитория
        $allRanks = $this->militaryRanksRepository->findAll();
        $titles   = [];
        foreach ($allRanks as $rank) {
            $title = trim((string)$rank->getTitle());
            if ($title !== '' && isset($usedRankTitles[$title])) {
                $titles[] = $title;
            }
        }
        sort($titles, SORT_LOCALE_STRING);

        // Формируем ответ
        $response = [
            'birth' => [
                'days'   => $this->getSortedList($birthDays,   SORT_NUMERIC),
                'months' => $this->getSortedMonthNamesList($birthMonths),
                'years'  => $this->getSortedList($birthYears,  SORT_NUMERIC),
            ],
            'death' => [
                'days'   => $this->getSortedList($deathDays,   SORT_NUMERIC),
                'months' => $this->getSortedMonthNamesList($deathMonths),
                'years'  => $this->getSortedList($deathYears,  SORT_NUMERIC),
            ],
            'letters'       => $this->getSortedList($letters,        SORT_LOCALE_STRING),
            'militaryRanks' => $titles,
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
}
