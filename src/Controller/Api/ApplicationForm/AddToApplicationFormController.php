<?php

namespace App\Controller\Api\ApplicationForm;

use App\Entity\ApplicationForm;
use App\Entity\ApplicationFormArchive;
use App\Entity\ApplicationFormHeroAward;
use App\Entity\ApplicationFormImages;
use App\Repository\ApplicationFormArchiveRepository;
use App\Repository\ApplicationFormHeroAwardRepository;
use App\Repository\ApplicationFormImagesRepository;
use App\Repository\ApplicationFormRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
class AddToApplicationFormController extends AbstractController
{
    public function __construct(
        private readonly ApplicationFormRepository $applicationFormRepository,
        private readonly ApplicationFormImagesRepository $applicationFormImagesRepository,
        private readonly ApplicationFormArchiveRepository $applicationFormArchiveRepository,
        private readonly ApplicationFormHeroAwardRepository $applicationFormHeroAwardRepository,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->request;
        $category = $data->get('category');

        if ($category) {
            $allowedCategories = [
                'Герои Великой Отечественной войны',
                'Труженики тыла',
                'Герои локальных войн',
                'Герои - ликвидаторы ЧС',
                'Герои СВО',
            ];

            if (!in_array($category, $allowedCategories, true)) {
                return $this->json('Категорию верно выберите.', 403);
            }
        }

        $applicationForm = $this->createApplicationForm($data);
        $this->handleHeroAwards($data->get('heroAward'), $applicationForm);
        $this->handleUploads($request, $applicationForm);

        return $this->json([
            "status" => "success",
            "details" => "ApplicationForm added successfully."
        ]);
    }

    private function createApplicationForm($data): ApplicationForm
    {
        $form = new ApplicationForm();

        $form->setSurname($data->get('surname'));
        $form->setName($data->get('name'));
        $form->setPatronymic($data->get('patronymic'));
        $form->setCity($data->get('city'));
        $form->setCategory($data->get('category'));
        $form->setMilitaryRank($data->get('militaryRank'));
        $form->setBirthDateAt($data->get('birthDateAt'));
        $form->setDeathDateAt($data->get('deathDateAt'));
        $form->setAdditional($data->get('additional'));
        $form->setSurnameSender($data->get('surnameSender'));
        $form->setNameSender($data->get('nameSender'));
        $form->setPatronymicSender($data->get('patronymicSender'));
        $form->setPhone($data->get('phone'));
        $form->setInstitute($data->get('institute') ?? 'Не указано');
        $form->setStatus('Не рассмотрена');
        $form->setCreatedAt(new \DateTime());
        $form->setUpdatedAt(new \DateTime());

        $this->applicationFormRepository->save($form, true);

        return $form;
    }

    private function handleHeroAwards(?string $raw, ApplicationForm $form): void
    {
        if (!$raw) return;
        $heroAwards = json_decode($raw, true);

        foreach ($heroAwards as $award) {
            if (!$award['title'] && !$award['yearAt'] && !$award['description']) continue;

            $entity = (new ApplicationFormHeroAward())
                ->setTitle($award['title'])
                ->setYearAt($award['yearAt'])
                ->setDescription($award['description']);

            $form->addHeroAward($entity);
            $this->applicationFormHeroAwardRepository->save($entity, true);
        }

        $this->applicationFormRepository->save($form, true);
    }

    private function handleUploads(Request $request, ApplicationForm $form): void
    {
        foreach ($request->files->get('images', []) as $image) {
            $this->saveImage($image, $form);
        }

        foreach ($request->files->get('archive', []) as $archive) {
            $this->saveArchive($archive, $form);
        }
    }

    private function saveImage(?File $image, ApplicationForm $form): void
    {
        $entity = (new ApplicationFormImages())->setImageFile($image);
        $form->addImage($entity);
        $this->applicationFormImagesRepository->save($entity, true);
    }

    private function saveArchive(?File $archive, ApplicationForm $form): void
    {
        $entity = (new ApplicationFormArchive())->setMediaFile($archive);
        $form->addArchive($entity);
        $this->applicationFormArchiveRepository->save($entity, true);
    }
}
