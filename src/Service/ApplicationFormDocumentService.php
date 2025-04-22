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
        private ApplicationFormRepository $applicationFormRepository,) {}

    public function process(ApplicationForm $form): void
    {
        if ($form->getQr()) {
            $oldQrPath = "/home/shared-backend/web/book-memory-admin.itlabs.top/public_html/public/media/application_form_qr/" . $form->getQr();
            if (file_exists($oldQrPath)) {
                unlink($oldQrPath);
            }
        }

        $docxPath = $this->generateDocxFromTemplate($form);

        $pdfUrl = $this->convertDocxToPdf($docxPath);

        $qrPath = $this->generateQrCode($pdfUrl);

        $form->setQr(basename($qrPath));
        $form->setQrFile(new File($qrPath));

        $this->applicationFormRepository->save($form, true);
    }

    private function generateDocxFromTemplate(ApplicationForm $form): string
    {
        $template = new TemplateProcessor('/home/shared-backend/web/book-memory-admin.itlabs.top/public_html/public/шаблон.docx');

        $template->setValues([
            'SURNAME' => $form->getSurname(),
            'NAME' => $form->getName(),
            'PATRONYMIC' => $form->getPatronymic(),
            'YEARSTART' => $form->getBirthDateAt(),
            'YEAREND' => $form->getDeathDateAt(),
            'PLACE' => $form->getCity(),
            'MILITARYRANK' => $form->getMilitaryRank(),
            'CATEGORY' => $form->getCategory(),
            'ADDITIONAL' => $form->getAdditional(),
        ]);

        $template->setValue('AWARDS_BLOCK', $this->formatAwards($form));

        $path = tempnam(sys_get_temp_dir(), 'form_') . '.docx';
        $template->saveAs($path);
        return $path;
    }

    private function convertDocxToPdf(string $docxPath): string
    {
        $outputDir = "/home/shared-backend/web/book-memory-admin.itlabs.top/public_html/public/media/application_form_pdf";
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

        return 'https://book-memory-admin.itlabs.top/public/media/application_form_pdf/' . basename($docxPath, '.docx') . '.pdf';
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

        $dir = "/home/shared-backend/web/book-memory-admin.itlabs.top/public_html/public/media/application_form_qr";
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $filePath = "$dir/" . uniqid('qr_', true) . ".png";
        $writer->write($qrCode)->saveToFile($filePath);
        return $filePath;
    }

    private function formatAwards(ApplicationForm $form): string
    {
        $text = '';
        foreach ($form->getHeroAward() as $award) {
            $text .= sprintf("%s, %s\n%s\n\n",
                $award->getTitle() ?? 'Название не указано',
                $award->getYearAt() ?? 'Год не указан',
                $award->getDescription() ?? 'Описание отсутствует');
        }
        return trim($text);
    }
}
