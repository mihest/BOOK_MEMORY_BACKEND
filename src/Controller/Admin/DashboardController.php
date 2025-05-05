<?php

namespace App\Controller\Admin;

use App\Entity\AiKeywordBanned;
use App\Entity\ApplicationForm;
use App\Entity\Exhibit;
use App\Entity\HeroAward;
use App\Entity\Institutions;
use App\Entity\MilitaryRanks;
use App\Entity\PersonalDataAccept;
use App\Repository\PersonalDataAcceptRepository;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraints\DateTime;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class DashboardController extends AbstractDashboardController
{
    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    #[Route(path: '/admin', name: 'admin')]
    public function index(): Response
    {
        $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);
        return $this->redirect($adminUrlGenerator->setController(MilitaryRanksCrudController::class)->generateUrl());
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('
                <span>Админ-панель</span>
            ')
            ->setFaviconPath('favicon.ico')
            ->renderContentMaximized()
            ->generateRelativeUrls();
    }

    public function __construct(
        private readonly PersonalDataAcceptRepository $personalDataAcceptRepository,
        private readonly HttpClientInterface $httpClient,
    ) {}

    public function configureMenuItems(): iterable
    {
        yield MenuItem::section('Заявки с формы');
        yield MenuItem::linkToCrud('Нерассмотренные', 'fa fa-calendar', ApplicationForm::class)
            ->setController(ApplicationFormWaitingCrudController::class);
        yield MenuItem::linkToCrud('Принятые', 'fa fa-calendar', ApplicationForm::class)
            ->setController(ApplicationFormAgreeCrudController::class);
        yield MenuItem::linkToCrud('Отклоненные', 'fa fa-calendar', ApplicationForm::class)
            ->setController(ApplicationFormDisagreeCrudController::class);
        yield MenuItem::linkToCrud('Автоматически принятые', 'fa fa-calendar', ApplicationForm::class)
            ->setController(ApplicationFormAutoAgreeCrudController::class);
        yield MenuItem::linkToCrud('Автоматически отклонённые', 'fa fa-calendar', ApplicationForm::class)
            ->setController(ApplicationFormAutoDisagreeCrudController::class);
        yield MenuItem::section('Заполнение');
        yield MenuItem::linkToCrud('Воинские звания', 'fas fa-list', MilitaryRanks::class);
        yield MenuItem::linkToCrud('Награды героев', 'fas fa-medal', HeroAward::class);
        yield MenuItem::linkToCrud('Организации', 'fas fa-list', Institutions::class);

        yield MenuItem::linkToCrud('Слова забаненные нейросетью', 'fa fa-list', AiKeywordBanned::class);

        if ($this->personalDataAcceptRepository->count([]) === 0) {
            yield MenuItem::linkToCrud('Согласие на обработку персональных данных', 'fa fa-info', PersonalDataAccept::class)
                ->setAction(Action::NEW);
        } else {
            yield MenuItem::linkToCrud('Согласие на обработку персональных данных', 'fa fa-info', PersonalDataAccept::class)
                ->setAction(Action::EDIT)->setEntityId($this->personalDataAcceptRepository->findAll()[0]->getId());
        }

        yield MenuItem::section('Настройки');
        yield MenuItem::linkToUrl('API', 'fa fa-link', '/api')->setLinkTarget('_blank')
            ->setPermission('ROLE_ADMIN');

        yield MenuItem::linkToRoute(
            'Запустить Backup',
            'fas fa-database',
            'admin_export_archive'
        )->setPermission('ROLE_ADMIN');

        yield MenuItem::linkToRoute(
            'Импортировать архив',
            'fas fa-upload',
            'admin_import_archive'
        )->setPermission('ROLE_ADMIN');
    }

    #[Route('/admin/export-archive', name: 'admin_export_archive')]
    public function exportAndUpload(): Response
    {
        $projectDir = $this->getParameter('kernel.project_dir');
        $dateTime = new \DateTime();
        $filename = $dateTime->format('Y-m-d_H-i-s') . '.zip';
        $outputFile = $projectDir . '/var/backups/' . $filename;

        if (!is_dir(dirname($outputFile))) {
            mkdir(dirname($outputFile), 0755, true);
        }

        $process = new Process([
            'php',
            $projectDir . '/bin/console',
            'app:backup',
            '--db-host=127.0.0.1',
            '--db-user=shared-backend_book-memory-admin',
            '--db-pass=Qwertyy1AAsdgsdgsdf',
            '--db-name=shared-backend_book-memory-admin',
            '--output=' . $outputFile,
            'public'
        ]);
        $process->setWorkingDirectory($projectDir);
        $process->setTimeout(3600);

        try {
            $process->mustRun();
        } catch (ProcessFailedException $e) {
            return new Response('❌ Ошибка при создании архива: ' . $e->getMessage(), 500);
        }

        $sftpHost = '94.181.95.94';
        $sftpPort = 22;
        $sftpUsername = 'user';
        $sftpPassword = 'Privedmedved70';
        $remotePath = '/home/user/topicIs/' . $filename;

        $connection = ssh2_connect($sftpHost, $sftpPort);
        if (!$connection) {
            return new Response('❌ Не удалось подключиться к серверу SFTP', 500);
        }

        if (!ssh2_auth_password($connection, $sftpUsername, $sftpPassword)) {
            return new Response('❌ Ошибка аутентификации на SFTP', 403);
        }

        $sftp = ssh2_sftp($connection);
        $stream = @fopen("ssh2.sftp://$sftp$remotePath", 'w');
        if (!$stream) {
            return new Response('❌ Не удалось открыть поток на SFTP', 500);
        }

        $localStream = fopen($outputFile, 'rb');
        stream_copy_to_stream($localStream, $stream);
        fclose($stream);
        fclose($localStream);

        $response = new BinaryFileResponse($outputFile);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $filename
        );

        return $response;
    }

    #[Route('/admin/import-archive', name: 'admin_import_archive')]
    public function importArchive(): Response
    {
        return $this->render('admin/field/import_archive.html.twig');
    }

    #[Route('/admin/handle-import-archive', name: 'admin_handle_import_archive', methods: ['POST'])]
    public function handleImportArchive(Request $request): Response
    {
        $uploadedFile = $request->files->get('archive');
        if (!$uploadedFile || $uploadedFile->getClientOriginalExtension() !== 'zip') {
            return new Response('❌ Неверный формат файла. Требуется .zip', 400);
        }

        $projectDir = $this->getParameter('kernel.project_dir');
        $tmpDir = $projectDir . '/var/tmp_import';
        if (!is_dir($tmpDir)) {
            mkdir($tmpDir, 0755, true);
        }

        $zipPath = $tmpDir . '/import.zip';
        $uploadedFile->move($tmpDir, 'import.zip');

        $zip = new \ZipArchive();
        if ($zip->open($zipPath) === true) {
            $zip->extractTo($tmpDir);
            $zip->close();
        } else {
            return new Response('❌ Ошибка при распаковке архива', 500);
        }

        $sqlFile = null;
        foreach (scandir($tmpDir) as $file) {
            if (str_ends_with($file, '.sql')) {
                $sqlFile = $tmpDir . '/' . $file;
                break;
            }
        }

        if (!$sqlFile || !file_exists($sqlFile)) {
            return new Response('❌ SQL файл не найден в архиве', 400);
        }

        $resetProcess = new Process([
            'mysql',
            '-h', '127.0.0.1',
            '-u', 'shared-backend_book-memory-admin',
            '-pQwertyy1AAsdgsdgsdf',
            '-e', 'DROP DATABASE IF EXISTS `shared-backend_book-memory-admin`; CREATE DATABASE `shared-backend_book-memory-admin`;'
        ]);
        $resetProcess->mustRun();

        $process = new Process([
            'mysql',
            '-h', '127.0.0.1',
            '-u', 'shared-backend_book-memory-admin',
            '-pQwertyy1AAsdgsdgsdf',
            'shared-backend_book-memory-admin'
        ]);

        $process->setInput(file_get_contents($sqlFile));
        $process->setTimeout(600);

        try {
            $process->run();
        } catch (ProcessFailedException $e) {
            return new Response('❌ Ошибка при импорте SQL: ' . $e->getMessage(), 500);
        }

        $sourcePublic = $tmpDir . '/public';
        $destPublic = $projectDir . '/public';

        if (!is_dir($sourcePublic)) {
            return new Response('❌ Папка public не найдена в архиве', 400);
        }

        $filesystem = new Filesystem();
        try {
            $filesystem->remove($destPublic);
            $filesystem->rename($sourcePublic, $destPublic);
        } catch (\Exception $e) {
            return new Response('❌ Ошибка при замене папки public: ' . $e->getMessage(), 500);
        }

        $this->addFlash('success', '✅ Импорт успешно завершён');

        return $this->redirectToRoute('admin');
    }
}
