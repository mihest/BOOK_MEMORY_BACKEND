<?php

namespace App\Controller\Api\ApplicationForm;

use App\Repository\ApplicationFormRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
class EditToApplicationFormController extends AbstractController
{

    public function __construct(
        private readonly ApplicationFormRepository $applicationFormRepository,)
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $applicationForm = $this->applicationFormRepository->find($data['id']);

        $applicationForm->setStatus($data['status']);
        $applicationForm->setChangedAi($data['changedAi']);
        $applicationForm->setChangedAiDescription($data['changedAiDescription']);

        $this->applicationFormRepository->save($applicationForm, true);

        return $this->json([
            "status" => "success",
            "details" => "ApplicationForm changed in this successfully.",
        ]);
    }
}
