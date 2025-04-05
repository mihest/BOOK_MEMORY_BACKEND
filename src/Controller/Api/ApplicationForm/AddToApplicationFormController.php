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
use App\Repository\HeroAwardRepository;
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
        private readonly HeroAwardRepository $heroAwardRepository,
    )
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->request;

        $applicationForm = new ApplicationForm();
        $applicationForm->setSurname($data->get('surname'));
        $applicationForm->setName($data->get('name'));
        $applicationForm->setPatronymic($data->get('patronymic'));
        $applicationForm->setCity($data->get('city'));
        $applicationForm->setCategory($data->get('category'));
        $applicationForm->setMilitaryRank($data->get('militaryRank'));
        $applicationForm->setBirthDateAt($data->get('birthDateAt'));
        $applicationForm->setDeathDateAt($data->get('deathDateAt'));
        $applicationForm->setAdditional($data->get('additional'));
        $applicationForm->setSurnameSender($data->get('surnameSender'));
        $applicationForm->setNameSender($data->get('nameSender'));
        $applicationForm->setPatronymicSender($data->get('patronymicSender'));
        $applicationForm->setPhone($data->get('phone'));

        $this->applicationFormRepository->save($applicationForm, true);

        $raw = $data->get('heroAward');

        if ($raw !== null)
        {
            $heroAwards = json_decode($raw, true);

            foreach ($heroAwards as $heroAward)
            {
                $entityNewThis = new ApplicationFormHeroAward();
                $entityNewThis->setHeroAward($this->heroAwardRepository->find($heroAward['id']));
                $entityNewThis->setYearAt($heroAward['yearAt']);
                $entityNewThis->setDescription($heroAward['description']);
                $entityNewThis->setApplicationForm($applicationForm);

                $this->applicationFormHeroAwardRepository->save($entityNewThis, true);
            }
        }

        $images = $request->files->get('images', []);

        foreach ($images as $image) {
            $this->processImageUpload($image, $applicationForm);
        }

        $archives = $request->files->get('archive', []);

        foreach ($archives as $archive) {
            $this->proccessArchiveUpload($archive, $applicationForm);
        }

        return $this->json([
            "status" => "success",
            "details" => "ApplicationForm added on this successfully.",
        ]);
    }

    private function processImageUpload(?File $image, ApplicationForm $applicationForm)
    {
        $applicationFormCarts = new ApplicationFormImages();
        $applicationFormCarts->setImageFile($image);
        $applicationFormCarts->setApplicationForm($applicationForm);

        $this->applicationFormImagesRepository->save($applicationFormCarts, true);
    }

    private function proccessArchiveUpload(?File $archive, ApplicationForm $applicationForm)
    {
        $applicationFormFiles = new ApplicationFormArchive();
        $applicationFormFiles->setMediaFile($archive);
        $applicationFormFiles->setApplicationForm($applicationForm);

        $this->applicationFormArchiveRepository->save($applicationFormFiles, true);
    }
}
