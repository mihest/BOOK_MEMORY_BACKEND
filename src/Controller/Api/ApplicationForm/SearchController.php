<?php

namespace App\Controller\Api\ApplicationForm;

use App\Repository\ApplicationFormRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;

class SearchController extends AbstractController
{
    public function __construct(
        private readonly ApplicationFormRepository $applicationFormRepository,
    ) {}

    public function __invoke(
        #[MapQueryParameter] ?int    $day          = null,
        #[MapQueryParameter] ?string $month        = null,
        #[MapQueryParameter] ?int    $year         = null,
        #[MapQueryParameter] ?string $city         = null,
        #[MapQueryParameter] ?string $letter       = null,
        #[MapQueryParameter] ?string $militaryRank = null,
        #[MapQueryParameter] ?string $name         = null,
        #[MapQueryParameter] ?string $category     = null,
        #[MapQueryParameter] ?int    $page         = 1,
        #[MapQueryParameter] ?int    $itemsPerPage = 15,
    ): JsonResponse
    {
        $initialLetter = $letter
            ? mb_strtoupper(mb_substr(trim($letter), 0, 1, 'UTF-8'), 'UTF-8')
            : null;

        $searchName = $name
            ? trim($name)
            : null;

        $members = $this->applicationFormRepository->findByFilters(
            day:          $day,
            month:        $month,
            year:         $year,
            city:         $city,
            letter:       $initialLetter,
            militaryRank: $militaryRank,
            name:         $searchName,
            category:     $category,
            page:         $page,
            itemsPerPage: $itemsPerPage,
        );

        if (
            $day    === null &&
            $month  === null &&
            $year   === null &&
            $city   === null &&
            $letter === null &&
            $militaryRank  === null &&
            $name   === null &&
            $category === null
        ) {
            $members = $this->applicationFormRepository->findAllPaginated($page, $itemsPerPage);
        }

        return $this->json(
            $members,
            Response::HTTP_OK,
            [],
            ['groups' => ['applicationForm:read']]
        );
    }
}
