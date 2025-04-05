<?php

namespace App\Controller\Admin;

use App\Entity\HeroAward;
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
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

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
    ) {}

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToCrud('Воинские звания', 'fas fa-list', MilitaryRanks::class);
        yield MenuItem::linkToCrud('Награды героев', 'fas fa-medal', HeroAward::class);

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

    }
}
