<?php

namespace App\Service;

use App\Entity\ApplicationForm;
use App\Repository\ApplicationFormRepository;
use PhpOffice\PhpWord\TemplateProcessor;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\HttpFoundation\File\File;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Writer\PngWriter;

readonly class ApplicationFormDocumentService
{
    public function __construct(
        private ApplicationFormRepository $applicationFormRepository,
        private string $projectDir,) {}

    public function process(ApplicationForm $form): void
    {
        if ($form->getQr()) {
            $oldQrPath = $this->projectDir . '/public/media/application_form_qr/' . $form->getQr();
            if (file_exists($oldQrPath)) {
                unlink($oldQrPath);
            }
        }

        if ($form->getPdf()) {
            $oldPdfPath = $this->projectDir . '/public/media/application_form_pdf/' . $form->getPdf();
            if (file_exists($oldPdfPath)) {
                unlink($oldPdfPath);
            }
        }

        $docxPath = $this->generateDocxFromTemplate($form);
        $pdfPath = $this->convertDocxToPdf($docxPath);

        $qrPath = $this->generateQrCode($pdfPath['url']);

        if (file_exists($qrPath)) {
            $form->setQr(basename($qrPath));
            $form->setQrFile(new File($qrPath));
        }

        if (file_exists($pdfPath['fullPath'])) {
            $form->setPdf($pdfPath['filename']);
            $form->setPdfFile(new File($pdfPath['fullPath']));
        }

        $this->applicationFormRepository->save($form, true);
    }

    private function generateDocxFromTemplate(ApplicationForm $form): string
    {
        $template = new TemplateProcessor($this->projectDir . '/public/шаблон.docx');

        $template->setValues([
            'SURNAME' => $form->getSurname() ?? 'Нет данных',
            'NAME' => $form->getName() ?? 'Нет данных',
            'PATRONYMIC' => $form->getPatronymic() ?? 'Нет данных',
            'YEARSTART' => $form->getBirthDateAt() ?? 'Нет данных',
            'YEAREND' => $form->getDeathDateAt() ?? 'Нет данных',
            'PLACE' => $form->getCity() ?? 'Нет данных',
            'MILITARYRANK' => $form->getMilitaryRank() ?? 'Нет данных',
            'CATEGORY' => $form->getCategory() ?? 'Нет данных',
            'ADDITIONALDATAFIRST' => $form->getAdditional() ?? 'Нет данных',
            'AWARDS_BLOCK' => $this->formatAwards($form) ?? 'Нет данных',
        ]);

        $images = $form->getImages();
        $template->cloneRow('IMAGE', count($images));

        foreach ($images as $i => $image) {
            $rowIndex = $i + 1;
            $imagePath = $this->projectDir . '/public/images/application_form/' . $image->getImage();

            if (file_exists($imagePath)) {
                $docxCompatiblePath = $this->ensureDocxCompatibleImage($imagePath);
                if ($docxCompatiblePath === null)
                {
                    continue;
                }
                $template->setImageValue("IMAGE#{$rowIndex}", [
                    'path' => $docxCompatiblePath,
                    'width' => 350,
                    'height' => 230,
                    'ratio' => true
                ]);

                if ($docxCompatiblePath !== $imagePath) {
                    @unlink($docxCompatiblePath);
                }
            } else {
                $template->setValue("IMAGE#{$rowIndex}", 'Изображение не найдено');
            }
        }

        $archives = $form->getArchive();
        $template->cloneRow('ARCHIVE', count($archives));

        foreach ($archives as $i => $archive) {
            $rowIndex = $i + 1;
            $archivePath = $this->projectDir . '/public/media/application_form/' . $archive->getMedia();

            if (file_exists($archivePath) && !str_contains($archivePath, '.pdf')) {
                $docxCompatiblePath = $this->ensureDocxCompatibleImage($archivePath);
                if ($docxCompatiblePath === null)
                {
                    continue;
                }
                $template->setImageValue("ARCHIVE#{$rowIndex}", [
                    'path' => $docxCompatiblePath,
                    'width' => 350,
                    'height' => 230,
                    'ratio' => true
                ]);

                if ($docxCompatiblePath !== $archivePath) {
                    @unlink($docxCompatiblePath);
                }
            } else {
                $template->setValue("ARCHIVE#{$rowIndex}", 'Изображение не найдено');
            }
        }

        $path = tempnam(sys_get_temp_dir(), 'form_') . '.docx';
        $template->saveAs($path);
        return $path;
    }

    private function convertDocxToPdf(string $docxPath): array
    {
        $outputDir = $this->projectDir . "/public/media/application_form_pdf";
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        $process = new Process([
            'libreoffice', '--headless', '--convert-to', 'pdf', '--outdir', $outputDir, $docxPath
        ]);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        $pdfFilename = basename($docxPath, '.docx') . '.pdf';
        return [
            'filename' => $pdfFilename,
            'fullPath' => $outputDir . '/' . $pdfFilename,
            'url' => 'https://book-memory-admin.itlabs.top/public/media/application_form_pdf/' . $pdfFilename
        ];
    }

    private function generateQrCode(string $pdfUrl): string
    {
        $writer = new PngWriter();
        $qrCode = new QrCode(
            data: $pdfUrl,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 300,
            margin: 10,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
            foregroundColor: new Color(0, 0, 0),
            backgroundColor: new Color(255, 255, 255),
        );

        $dir = $this->projectDir . "/public/media/application_form_qr";
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $filePath = "$dir/" . uniqid('qr_', true) . ".png";
        $writer->write($qrCode)->saveToFile($filePath);
        return $filePath;
    }

    private function formatAwards(ApplicationForm $form): ?string
    {
        $text = '';
        foreach ($form->getHeroAward() as $award) {
            $text .= sprintf("%s\n%s\n%s\n\n",
                $award->getTitle() ?? 'Название не указано',
                $award->getYearAt() ?? 'Год не указан',
                $award->getDescription() ?? 'Описание отсутствует');
        }
        return trim($text) === '' ? null : $text;
    }

    private function ensureDocxCompatibleImage(string $path): string | null
    {
        if (!is_file($path)) {
            return null;
        }

        $info = getimagesize($path);
        $mime = $info['mime'] ?? '';

        if ($mime === 'image/webp') {
            $image = imagecreatefromwebp($path);
            $newPath = sys_get_temp_dir() . '/' . uniqid('img_docx_', true) . '.png';
            imagepng($image, $newPath);
            imagedestroy($image);
            return $newPath;
        }

        return $path;
    }
}
