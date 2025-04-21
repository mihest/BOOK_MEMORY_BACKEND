<?php

namespace App\Controller\Api\ApplicationForm;

use App\Controller\Api\CalculatingExportPdf\input\CalculatingExportPdfRequest;
use App\Entity\ApplicationForm;
use App\Entity\ApplicationFormArchive;
use App\Entity\ApplicationFormHeroAward;
use App\Entity\ApplicationFormImages;
use App\Entity\Employee;
use App\Repository\ApplicationFormArchiveRepository;
use App\Repository\ApplicationFormHeroAwardRepository;
use App\Repository\ApplicationFormImagesRepository;
use App\Repository\ApplicationFormRepository;
use App\Repository\HeroAwardRepository;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use PhpOffice\PhpWord\TemplateProcessor;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

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
        $applicationForm->setInstitute($data->get('institute') ?? 'Не указано');
        $applicationForm->setStatus('Не рассмотрена');
        $applicationForm->setCreatedAt(new \DateTime());
        $applicationForm->setUpdatedAt(new \DateTime());

        $this->applicationFormRepository->save($applicationForm, true);

        $raw = $data->get('heroAward');

        if ($raw !== null)
        {
            $heroAwards = json_decode($raw, true);

            foreach ($heroAwards as $heroAward)
            {
                if ($heroAward['title'] === null && $heroAward['yearAt'] === null && $heroAward['description'] === null)
                {
                    continue;
                }

                $entityNewThis = new ApplicationFormHeroAward();
                $entityNewThis->setTitle($heroAward['title']);
                $entityNewThis->setYearAt($heroAward['yearAt']);
                $entityNewThis->setDescription($heroAward['description']);
                $applicationForm->addHeroAward($entityNewThis);

                $this->applicationFormHeroAwardRepository->save($entityNewThis, true);
                $this->applicationFormRepository->save($applicationForm, true);
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

        $docxPath = $this->generateDocxFromTemplate($applicationForm);

        $pdfPath = $this->convertDocxToPdf($docxPath);

        $qrCodePath = $this->generateQrCode($pdfPath);

        $applicationForm->setQr(basename($qrCodePath))->setQrFile(new File($qrCodePath));

        $this->applicationFormRepository->save($applicationForm, true);

        return $this->json([
            "status" => "success",
            "details" => "ApplicationForm added on this successfully.",
        ]);
    }

    private function processImageUpload(?File $image, ApplicationForm $applicationForm)
    {
        $applicationFormCarts = new ApplicationFormImages();
        $applicationFormCarts->setImageFile($image);
        $applicationForm->addImage($applicationFormCarts);

        $this->applicationFormImagesRepository->save($applicationFormCarts, true);
        $this->applicationFormRepository->save($applicationForm, true);
    }

    private function proccessArchiveUpload(?File $archive, ApplicationForm $applicationForm)
    {
        $applicationFormFiles = new ApplicationFormArchive();
        $applicationFormFiles->setMediaFile($archive);
        $applicationForm->addArchive($applicationFormFiles);

        $this->applicationFormArchiveRepository->save($applicationFormFiles, true);
        $this->applicationFormRepository->save($applicationForm, true);
    }

    private function generateDocxFromTemplate(ApplicationForm $applicationForm): string
    {
        $templatePath = $this->getParameter('kernel.project_dir').'/public/шаблон.docx';
        $templateProcessor = new TemplateProcessor($templatePath);

        $templateProcessor->setValue('SURNAME', $applicationForm->getSurname());
        $templateProcessor->setValue('NAME', $applicationForm->getName());
        $templateProcessor->setValue('PATRONYMIC', $applicationForm->getPatronymic());
        $templateProcessor->setValue('YEARSTART', $applicationForm->getBirthDateAt());
        $templateProcessor->setValue('YEAREND', $applicationForm->getDeathDateAt());
        $templateProcessor->setValue('PLACE', $applicationForm->getCity());
        $templateProcessor->setValue('MILITARYRANK', $applicationForm->getMilitaryRank());
        $templateProcessor->setValue('CATEGORY', $applicationForm->getCategory());
        $templateProcessor->setValue('ADDITIONAL', $applicationForm->getAdditional());

        $awardsHeroes = $applicationForm->getHeroAward();
        $awardsText = '';

        foreach ($awardsHeroes as $award) {
            $awardsText .= sprintf(
                "%s, %s\n%s\n\n",
                $award->getTitle() ?? 'Название не указано',
                $award->getYearAt() ?? 'Год не указан',
                $award->getDescription() ?? 'Описание отсутствует'
            );
        }

        $templateProcessor->setValue('AWARDS_BLOCK', trim($awardsText));

        $images = $applicationForm->getImages();
        $templateProcessor->cloneRow('IMAGE', count($images));

        foreach ($images as $i => $image) {
            $rowIndex = $i + 1;
            $imagePath = $this->getParameter('kernel.project_dir') . '/public/images/application_form/' . $image->getImage(); // Путь к изображению

            if (file_exists($imagePath)) {
                $templateProcessor->setImageValue("IMAGE_{$rowIndex}", [
                    'path' => $imagePath,
                    'width' => 350,
                    'height' => 230,
                    'ratio' => true
                ]);
            } else {
                error_log("Image not found: " . $imagePath);
                $templateProcessor->setValue("IMAGE_{$rowIndex}", 'Изображение не найдено');
            }
        }

        $archives = $applicationForm->getArchive();
        $templateProcessor->cloneRow('ARCHIVE', count($archives));

        foreach ($archives as $i => $archive) {
            $rowIndex = $i + 1;
            $archivePath = $this->getParameter('kernel.project_dir') . '/public/media/application_form/' . $archive->getMedia();

            if (file_exists($archivePath)) {
                $templateProcessor->setValue("ARCHIVE_{$rowIndex}", $archivePath);
            } else {
                $templateProcessor->setValue("ARCHIVE_{$rowIndex}", 'Архив не найден');
            }
        }

        $tempDocxPath = tempnam(sys_get_temp_dir(), 'application_form_pdf_') . '.docx';
        $templateProcessor->saveAs($tempDocxPath);

        return $tempDocxPath;
    }

    private function convertDocxToPdf(string $docxPath): string
    {
        $uniqueName = uniqid('application_form_pdf_', true);
        $outputPdfPath = $this->getParameter('kernel.project_dir')
            ."/public/media/application_form_pdf/{$uniqueName}.pdf";

        $outputDir = \dirname($outputPdfPath);
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        $process = new Process([
            'libreoffice',
            '--headless',
            '--convert-to', 'pdf',
            '--outdir', $outputDir,
            $docxPath,
        ]);

        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        $generatedPdfFile = pathinfo($docxPath, PATHINFO_FILENAME).'.pdf';

        return 'https://book-memory-admin.itlabs.top/public/media/application_form_pdf/'.$generatedPdfFile;
    }

    private function generateQrCode(string $pdfPath): string
    {
        $writer = new PngWriter();
        $qrCode = new QrCode(
            data: $pdfPath,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 300,
            margin: 10,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
            foregroundColor: new Color(0, 0, 0),
            backgroundColor: new Color(255, 255, 255)
        );

        $result = $writer->write($qrCode);
        $outputQrCodePath = $this->getUniqueFilePath();
        $result->saveToFile($outputQrCodePath);

        return $outputQrCodePath;
    }

    private function getUniqueFilePath(): string
    {
        $uniqueName = uniqid('application_form_qr', true);
        $dirPath = $this->getParameter('kernel.project_dir') . "/public/media/application_form_qr";

        if (!is_dir($dirPath)) {
            mkdir($dirPath, 0755, true);
        }

        return "$dirPath/$uniqueName.png";
    }

    private function generateImagesBlock(array $imagePaths): string
    {
        $block = '';
        foreach ($imagePaths as $path) {
            if (file_exists($path)) {
                $block .= sprintf(
                    '<img src="%s" width="350" height="230" />',
                    $path
                );
            }
        }
        return $block ?: 'Нет изображений';
    }

    private function getImagePath(string $imageName): string
    {
        return $this->getParameter('kernel.project_dir') . '/public/images/application_form_images/' . $imageName;
    }
}
