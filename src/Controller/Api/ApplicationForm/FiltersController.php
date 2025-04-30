<?php

namespace App\Controller\Api\ApplicationForm;

use App\Repository\ApplicationFormRepository;
use App\Repository\MilitaryRanksRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;

class FiltersController extends AbstractController
{
    private const array MONTH_MAP = [
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

    public function __invoke(#[MapQueryParameter] ?string $category            = null,): JsonResponse
    {
        $members = $this->applicationFormRepository->findBy(['status' => 'Принята', 'category' => $category]);

        $letters        = [];
        $usedRankTitles = [];

        foreach ($members as $member) {
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

            $surname = trim((string)$member->getSurname());
            if ($surname !== '' && $surname !== '-') {
                $clean = preg_replace('/[^А-Яа-яЁё]/u', '', $surname);
                if ($clean !== '') {
                    $letter = mb_strtoupper(mb_substr($clean, 0, 1, 'UTF-8'), 'UTF-8');
                    $letters[$letter] = true;
                }
            }

            $rankString = trim((string)$member->getMilitaryRank());
            if ($rankString !== '') {
                $usedRankTitles[$rankString] = true;
            }
        }

        $allRanks = $this->militaryRanksRepository->findBy(['category' => $category]);
        $titles   = [];
        foreach ($allRanks as $rank) {
            $title = trim((string)$rank->getTitle());
            if ($title !== '' && isset($usedRankTitles[$title])) {
                $titles[] = $title;
            }
        }
        sort($titles, SORT_LOCALE_STRING);

        $response = [
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
}
