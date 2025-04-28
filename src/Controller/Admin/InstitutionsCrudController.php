<?php

namespace App\Controller\Admin;

use App\Entity\ApplicationForm;
use App\Entity\Institutions;
use App\Repository\ApplicationFormRepository;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use ZipArchive;

class InstitutionsCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Institutions::class;
    }

    public function __construct(private readonly ApplicationFormRepository $applicationFormRepository)
    {
    }

    public function configureCrud(Crud $crud): Crud
    {
        return parent::configureCrud($crud)
            ->setEntityLabelInPlural('Организации')
            ->setEntityLabelInSingular('Организацию')
            ->setPageTitle(Crud::PAGE_NEW, 'Добавление организации')
            ->setPageTitle(Crud::PAGE_EDIT, 'Изменение организации');
    }

    public function configureActions(Actions $actions): Actions
    {
        $exportAction = Action::new('exportData', 'Экспорт PDF')
            ->linkToCrudAction('exportData')
            ->setIcon('fa fa-file-archive')
            ->setCssClass('btn btn-success');

        return $actions
            ->add(Crud::PAGE_INDEX, $exportAction)
            ->add(Crud::PAGE_DETAIL, $exportAction);
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('title', 'Название')
            ->setColumns(8);

        yield IntegerField::new('count', 'Количество')
            ->setColumns(8);

        yield IntegerField::new('countAccepts', 'Количество принятых')
            ->setColumns(8);
    }

    /**
     * Экспорт всех PDF-заявок для данной организации в ZIP-архив.
     */
    public function exportData(AdminContext $context): BinaryFileResponse | Response
    {
        /** @var Institutions $institution */
        $institution = $context->getEntity()->getInstance();

        $forms = $this->applicationFormRepository->findBy([
            'institute' => $institution->getTitle(),
            'status' => 'Принята',
        ]);

        if (!$forms)
        {
            $this->addFlash('danger', 'Нет доступных pdf, файлов для выгрузки по организации.');

            return $this->redirectToRoute('admin_institutions_index');
        }

        $originalTitle = $institution->getTitle();
        $safeTitle = preg_replace('/[^\p{L}\p{N}_-]+/u', '_', $originalTitle);
        $safeTitle = preg_replace('/_+/', '_', $safeTitle);
        $safeTitle = trim($safeTitle, '_');

        $zipFileName = sprintf('%d_%s_%s.zip',
            $institution->getId(),
            $safeTitle,
            date('Y-m-d')
        );

        $tempFile = tempnam(sys_get_temp_dir(), 'zip');

        $zip = new ZipArchive();
        if ($zip->open($tempFile, ZipArchive::CREATE) !== true) {
            throw new \RuntimeException('Не удалось создать ZIP-архив.');
        }

        foreach ($forms as $form) {
            /** @var ApplicationForm $form */
            $pdfName = $form->getPdf();
            if (!$pdfName) {
                continue;
            }

            $filePath = $this->getParameter('kernel.project_dir')
                . '/public/media/application_form_pdf/'
                . $pdfName;

            if (!file_exists($filePath)) {
                continue;
            }

            $nameParts = [
                $form->getSurname(),
                $form->getName(),
            ];
            if ($form->getPatronymic()) {
                $nameParts[] = $form->getPatronymic();
            }
            $submissionDate = $form->getUpdatedAt()
                ? $form->getUpdatedAt()->format('Y-m-d')
                : date('Y-m-d');
            $nameParts[] = $submissionDate;

            $entryName = implode('_', array_map(
                fn($part) => preg_replace('/[^\p{L}\p{N}_-]+/u', '_', $part),
                $nameParts
            ));
            $entryName = preg_replace('/_+/', '_', $entryName);
            $entryName = trim($entryName, '_') . '.pdf';

            $zip->addFile($filePath, $entryName);
        }

        $zip->close();

        $response = new BinaryFileResponse($tempFile);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $zipFileName
        );

        return $response;
    }
}
