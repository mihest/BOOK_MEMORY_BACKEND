<?php

namespace App\Controller\Admin;

use App\Entity\AiKeywordBanned;
use App\Repository\AiKeywordBannedRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AiKeywordBannedCrudController extends AbstractCrudController
{
    private const int BATCH_SIZE = 50;

    public static function getEntityFqcn(): string
    {
        return AiKeywordBanned::class;
    }

    public function __construct(private readonly AiKeywordBannedRepository $aiKeywordBannedRepository,
                                private readonly HttpClientInterface $httpClient,
                                private readonly EntityManagerInterface $entityManager)
    {
    }

    public function configureCrud(Crud $crud): Crud
    {
        return parent::configureCrud($crud)
            ->setEntityLabelInPlural('Слова забаненные нейросетью')
            ->setEntityLabelInSingular('слово')
            ->setPageTitle(Crud::PAGE_NEW, 'Добавление слова')
            ->setPageTitle(Crud::PAGE_EDIT, 'Изменение слова');
    }

    public function configureActions(Actions $actions): Actions
    {
        $loadAction = Action::new('loadAiKeywordBanned', 'Загрузить слова')
            ->setHtmlAttributes(['data-bs-toggle' => 'modal', 'data-bs-target' => '#loadAiKeywordBanned'])
            ->linkToUrl('#')
            ->createAsGlobalAction();

        $exportAction = Action::new('exportAiKeywordBanned', 'Выгрузить слова')
            ->linkToCrudAction('export')
            ->createAsGlobalAction();

        $actions
            ->add(Crud::PAGE_INDEX, $loadAction)
            ->add(Crud::PAGE_INDEX, $exportAction)
            ->disable(Action::NEW, Action::DELETE, Action::EDIT);

        return parent::configureActions($actions);
    }

    public function configureAssets(Assets $assets): Assets
    {
        return parent::configureAssets($assets)
            ->addHtmlContentToBody('
                        <div id="loadAiKeywordBanned" class="modal fade" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-body p-0">
                                         <form enctype="multipart/form-data" action="/admin/ai-keyword-banned/import-popup" method="post" >
                                             <div class="filter-field border-bottom py-4 px-3 d-flex flex-column gap-4" data-filter-property="buyer"> 
                                              <input class="form-control" type="file" id="file" name="file">
                                              <input class="btn btn-success" type="submit" value="Отправить файл" />
                                             </div>
                                         </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>');
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')->hideOnForm();
        yield TextField::new('title', 'Наименование')->setColumns(8);
    }

    #[Route(path: '/admin/ai-keyword-banned/import-popup', name: 'ai_keyword_banned_import_popup', methods: ['POST'])]
    public function importPopup(Request $request): Response
    {
        $file = $request->files->get('file');
        if (!$file || !$file->isValid()) {
            $this->addFlash('danger', 'Ошибка при загрузке файла.');
            return $this->redirect($request->headers->get('referer') ?? '/admin');
        }

        // Батчевое удаление существующих записей через итерацию
        $query = $this->entityManager
            ->createQueryBuilder()
            ->select('e')
            ->from(AiKeywordBanned::class, 'e')
            ->getQuery();

        /** @var \Generator<AiKeywordBanned> $iterable */
        $iterable = $query->toIterable([], Query::HYDRATE_OBJECT);
        $i = 0;
        foreach ($iterable as $item) {
            $this->entityManager->remove($item);
            if ((++$i % self::BATCH_SIZE) === 0) {
                $this->entityManager->flush();
                $this->entityManager->clear();
            }
        }
        $this->entityManager->flush();
        $this->entityManager->clear();

        // Очищаем внешний API
        $this->httpClient->request('DELETE', 'http://94.181.95.94:5005/api/v1/text/remove-all-words');

        // Читаем и обрабатываем слова
        $lines = file($file->getRealPath(), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $words = array_unique(
            array_filter(
                array_map('mb_strtolower', array_map('trim', $lines))
            )
        );

        if (empty($words)) {
            $this->addFlash('warning', 'Нет слов для импорта.');
            return $this->redirect($request->headers->get('referer') ?? '/admin');
        }

        // Батчевый импорт новых слов
        $i = 0;
        foreach ($words as $word) {
            $entity = new AiKeywordBanned();
            $entity->setTitle($word);
            $this->entityManager->persist($entity);

            if ((++$i % self::BATCH_SIZE) === 0) {
                $this->entityManager->flush();
                $this->entityManager->clear();
            }
        }
        $this->entityManager->flush();
        $this->entityManager->clear();

        // Отправляем слова во внешний API
        $response = $this->httpClient->request('POST', 'http://94.181.95.94:5005/api/v1/text/add-words', [
            'json' => ['words' => array_values($words)],
        ]);

        if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
            $this->addFlash('success', 'Слова успешно импортированы в админку и в API!');
        } else {
            $this->addFlash('danger', 'Ошибка при отправке слов в API (код ' . $response->getStatusCode() . ')');
        }

        return $this->redirect($request->headers->get('referer') ?? '/admin');
    }

    public function export(): Response
    {
        $repo = $this->aiKeywordBannedRepository;
        $words = $repo->findAll();

        $lines = [];
        foreach ($words as $word) {
            $lines[] = $word->getTitle();
        }
        $content = implode("\n", $lines);

        $filename = 'ai_keyword_banned_' . date('Y-m-d_H-i-s') . '.txt';

        return new Response($content, 200, [
            'Content-Type' => 'text/plain',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
