<?php


namespace App\Controller\Admin\Traits;

use App\Controller\Admin\ApplicationFormArchiveCrudController;
use App\Controller\Admin\ApplicationFormHeroAwardCrudController;
use App\Controller\Admin\ApplicationFormImagesCrudController;
use App\Controller\Admin\Field\VichFileField;
use App\Controller\Admin\Field\VichGalleryField;
use App\Controller\Admin\Field\VichImageField;
use App\Service\ApplicationFormDocumentService;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

trait ApplicationFormFieldsTrait
{
    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance->getStatus() === 'Принята') {
            $entityInstance->setStatus('Не рассмотрена');
        }

        parent::updateEntity($entityManager, $entityInstance);
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
        yield TextField::new('additional', 'Дополнительные сведения')
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
        yield TextField::new('institute', 'Организация')
            ->setColumns(8);
        yield ChoiceField::new('status', 'Статус')
            ->setColumns(8)
            ->setChoices([
                'Не рассмотрена' => 'Не рассмотрена',
                'Принята' => 'Принята',
                'Отклонена' => 'Отклонена',
                'Автоматически принята' => 'Автоматически принята',
                'Автоматически отклонена' => 'Автоматически отклонена',
            ])
            ->onlyOnDetail();
        yield DateTimeField::new('createdAt', 'Дата создания')
            ->setColumns(8)
            ->hideOnForm();
        yield VichFileField::new('pdf', 'pdf-File')
            ->onlyOnDetail();

        yield FormField::addTab('Изображения');
        yield CollectionField::new('images', 'Изображения')
            ->setRequired(false)
            ->showEntryLabel(false)
            ->useEntryCrudForm(ApplicationFormImagesCrudController::class)
            ->setColumns(8)
            ->onlyOnForms();
        yield VichGalleryField::new('images.image', 'Изображения')
            ->onlyOnDetail();

        yield FormField::addTab('Архив');
        yield CollectionField::new('archive', 'Архив')
            ->setRequired(false)
            ->showEntryLabel(false)
            ->useEntryCrudForm(ApplicationFormArchiveCrudController::class)
            ->setColumns(8)
            ->onlyOnForms();
        yield VichGalleryField::new('archive.media', 'Архив')
            ->onlyOnDetail();

        yield FormField::addTab('Награды героя');
        yield CollectionField::new('heroAward', 'Награды героя')
            ->setRequired(false)
            ->showEntryLabel(false)
            ->useEntryCrudForm(ApplicationFormHeroAwardCrudController::class)
            ->setColumns(8)
            ->onlyOnForms();
        yield TextEditorField::new('heroAwardAll', 'Награды героя')
            ->setTemplatePath('/admin/field/text_editor.html.twig')
            ->onlyOnDetail();

        yield FormField::addTab('Ai')
            ->hideOnForm()
            ->hideOnIndex();
        yield BooleanField::new('changedAi', 'Проверено АИ')
            ->onlyOnDetail();
        yield TextField::new('changedAiDescription', 'Комментарий АИ проверки')
            ->hideOnForm()
            ->hideOnIndex();

        yield FormField::addTab('Qr-Code')
            ->hideOnForm()
            ->hideOnIndex();
        yield VichImageField::new('qr', 'qr-Code')
            ->onlyOnDetail();
    }
}
