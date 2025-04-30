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
        #[MapQueryParameter] ?string $city                = null,
        #[MapQueryParameter] ?string $letter              = null,
        #[MapQueryParameter] ?string $militaryRank        = null,
        #[MapQueryParameter] ?string $name                = null,
        #[MapQueryParameter] ?string $category            = null,
        #[MapQueryParameter] ?int    $page                = 1,
        #[MapQueryParameter] ?int    $itemsPerPage        = 15,
    ): JsonResponse
    {
        $initialLetters = $letter
            ? array_map(
                fn($l) => mb_strtoupper(trim($l), 'UTF-8'),
                explode(',', $letter)
            )
            : null;

        $searchName = $name
            ? trim($name)
            : null;

        $noFilters = (
            $city         === null &&
            $initialLetters === null &&
            $militaryRank === null &&
            $searchName   === null &&
            $category     === null
        );

        if ($noFilters) {
            $members = $this->applicationFormRepository->findAllPaginated($page, $itemsPerPage);
        } else {
            $members = $this->applicationFormRepository->findByFilters(
                city:         $city,
                letters:      $initialLetters,
                militaryRank: $militaryRank,
                name:         $searchName,
                category:     $category,
                page:         $page,
                itemsPerPage: $itemsPerPage,
            );
        }

        return $this->json(
            $members,
            Response::HTTP_OK,
            [],
            ['groups' => ['applicationForm:read']]
        );
    }
}
