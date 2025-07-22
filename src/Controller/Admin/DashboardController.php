<?php

namespace App\Controller\Admin;

use App\Entity\HeroAward;
use App\Entity\MilitaryRanks;
use App\Entity\People;
use App\Entity\User;
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

    public function __construct() {}

    public function configureMenuItems(): iterable
    {
        yield MenuItem::subMenu('Люди', 'fas fa-list')->setSubItems([
            MenuItem::linkToCrud('Герои Советского союза, РФ и полные кавалеры ордена славы', 'fas fa-user', People::class)
                ->setController(PeopleRfController::class),
            MenuItem::linkToCrud('Герои СВО', 'fas fa-user', People::class)
                ->setController(PeopleSvoController::class),
            MenuItem::linkToCrud('Герои ВОВ', 'fas fa-user', People::class)
                ->setController(PeopleVovController::class),
            MenuItem::linkToCrud('Локальные конфликты', 'fas fa-user', People::class)
                ->setController(PeopleLocalController::class),
            MenuItem::linkToCrud('Авария на Чернобыльской АЭС', 'fa fa-user', People::class)
                ->setController(PeopleChernobylController::class)
        ]);

        yield MenuItem::linkToCrud('Воинские звания', 'fas fa-list', MilitaryRanks::class);
        yield MenuItem::linkToCrud('Награды героев', 'fas fa-medal', HeroAward::class);

        yield MenuItem::section('Настройки');
        yield MenuItem::linkToCrud('Пользователи', 'fas fa-user-gear', User::class);
        yield MenuItem::linkToUrl('API', 'fa fa-link', '/api')->setLinkTarget('_blank')
            ->setPermission('ROLE_ADMIN');

    }
}
