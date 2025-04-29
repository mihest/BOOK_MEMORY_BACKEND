<?php

namespace App\Controller\Admin;

use App\Controller\Admin\Field\VichGalleryField;
use App\Controller\Admin\Traits\ApplicationFormFieldsTrait;
use App\Entity\ApplicationForm;
use App\Repository\ApplicationFormRepository;
use App\Repository\InstitutionsRepository;
use App\Service\ApplicationFormApprovalService;
use App\Service\ApplicationFormDocumentService;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\RedirectResponse;

class ApplicationFormDisagreeCrudController extends AbstractCrudController
{
    use ApplicationFormFieldsTrait;

    public static function getEntityFqcn(): string
    {
        return ApplicationForm::class;
    }

    public function __construct(private readonly ApplicationFormApprovalService $approvalService,
                                private readonly ApplicationFormDocumentService $applicationFormDocumentService,)
    {
    }

    public function configureCrud(Crud $crud): Crud
    {
        return parent::configureCrud($crud)
            ->setEntityLabelInPlural('Заявки с форм')
            ->setEntityLabelInSingular('заявку')
            ->setPageTitle(Crud::PAGE_NEW, 'Добавление заявки')
            ->setPageTitle(Crud::PAGE_EDIT, 'Изменение заявки');
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $queryBuilder = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);

        $queryBuilder->andWhere('entity.status = :status')
            ->setParameter('status', 'Отклонена');

        return $queryBuilder;
    }

    public function configureActions(Actions $actions): Actions
    {
        $agree = Action::new('agree', 'Принять')
            ->linkToCrudAction('agree')
            ->setCssClass('btn btn-success')
            ->displayIf(static function ($entity) {
                return $entity->getStatus() !== 'Принята';
            });

        return $actions
            ->add(Crud::PAGE_DETAIL, $agree)
            ->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function agree(AdminUrlGenerator $adminUrlGenerator): RedirectResponse
    {
        $form = $this->getContext()->getEntity()->getInstance();
        $this->approvalService->approve($form);

        $url = $adminUrlGenerator
            ->setController(self::class)
            ->setAction(Action::DETAIL)
            ->setEntityId($form->getId())
            ->generateUrl();

        return $this->redirect($url);
    }
}