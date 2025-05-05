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
use Symfony\Component\HttpFoundation\BinaryFileResponse;
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
}
