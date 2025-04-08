<?php

namespace App\Controller\Admin;

use App\Controller\Admin\Field\VichGalleryField;
use App\Entity\ApplicationForm;
use App\Repository\ApplicationFormRepository;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\RedirectResponse;

class ApplicationFormDisagreeCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ApplicationForm::class;
    }

    public function __construct(private readonly ApplicationFormRepository $applicationFormRepository,)
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
        /** @var ApplicationForm $applicationForm */
        $applicationForm = $this->getContext()->getEntity()->getInstance();
        $applicationForm->setStatus('Принята');
        $this->applicationFormRepository->save($applicationForm, true);

        $url = $adminUrlGenerator
            ->setController(self::class)
            ->setAction(Action::DETAIL)
            ->setEntityId($applicationForm->getId())
            ->generateUrl();

        return $this->redirect($url);
    }

    public function configureFields(string $pageName): iterable
    {
        yield FormField::addTab('Главная');
        yield TextField::new('surname', 'Фамилия')
            ->setColumns(8);
        yield TextField::new('name', 'Имя')
            ->setColumns(8);
        yield TextField::new('patronymic', 'Отчество')
            ->setColumns(8);
        yield TextField::new('city', 'Место рождения')
            ->setColumns(8);
        yield TextField::new('category', 'Категория героя')
            ->setColumns(8);
        yield TextField::new('militaryRank', 'Воинское звание')
            ->setColumns(8);
        yield TextField::new('birthDateAt', 'Дата рождения')
            ->setColumns(8);
        yield TextField::new('deathDateAt', 'Дата смерти')
            ->setColumns(8);
        yield TextEditorField::new('additional', 'Дополнительные сведения')
            ->setColumns(8)
            ->setTemplatePath('/admin/field/text_editor.html.twig');
        yield TextField::new('surnameSender', 'Фамилия отправителя')
            ->onlyOnForms()
            ->setColumns(8);
        yield TextField::new('nameSender', 'Имя отправителя')
            ->onlyOnForms()
            ->setColumns(8);
        yield TextField::new('patronymicSender', 'Отчество отправителя')
            ->onlyOnForms()
            ->setColumns(8);
        yield TextField::new('phone', 'Номер телефона отправителя')
            ->onlyOnForms()
            ->setColumns(8);
        yield TextEditorField::new('sender', 'Получатель')
            ->hideOnForm()
            ->setTemplatePath('/admin/field/text_editor.html.twig');
        yield ChoiceField::new('status', 'Статус')
            ->setColumns(8)
            ->setChoices
            (
                [
                    'Не рассмотрена' => 'Не рассмотрена',
                    'Принята' => 'Принята',
                    'Отклонена' => 'Отклонена',
                ]
            );

        yield FormField::addTab('Изображения');
        yield CollectionField::new('images', 'Избражения')
            ->setRequired(false)
            ->showEntryLabel(false)
            ->useEntryCrudForm(ApplicationFormImagesCrudController::class)
            ->setColumns(8)
            ->onlyOnForms();

        yield VichGalleryField::new('images.image', 'Избражения')
            ->onlyOnDetail();

        yield FormField::addTab('Архив');
        yield CollectionField::new('archive', 'Архив')
            ->setRequired(false)
            ->showEntryLabel(false)
            ->useEntryCrudForm(ApplicationFormArchiveCrudController::class)
            ->setColumns(8)
            ->onlyOnForms();

        yield TextField::new('media', 'Архив')
            ->setColumns(8)
            ->renderAsHtml()
            ->onlyOnDetail();

        yield FormField::addTab('Награды героя');
        yield CollectionField::new('heroAward', 'Награды героя')
            ->setRequired(false)
            ->showEntryLabel(false)
            ->useEntryCrudForm(ApplicationFormHeroAwardCrudController::class)
            ->setColumns(8)
            ->onlyOnForms();

        yield TextEditorField::new('heroAwardAll', 'Награды героя')
            ->hideOnForm()
            ->setTemplatePath('/admin/field/text_editor.html.twig');
    }
}