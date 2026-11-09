<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Assortment;
use App\Entity\Client;
use App\Controller\Admin\TaskCrudController;
use App\Entity\Industry;
use App\Entity\Source;
use App\Entity\Task;
use App\Entity\User;
use App\Form\QuickCompletedTaskType;
use App\Form\QuickTaskType;
use App\Service\ClientEmailExporter;
use App\Service\ClientListQueryFilter;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Exception\ForbiddenActionException;
use EasyCorp\Bundle\EasyAdminBundle\Factory\FilterFactory;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Security\Permission;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TelephoneField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class ClientCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly Security $security,
        private readonly EntityManagerInterface $em,
        private readonly ClientListQueryFilter $clientListQueryFilter,
        private readonly ClientEmailExporter $clientEmailExporter,
    ) {
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $qb = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);

        $request = $this->requestStack->getCurrentRequest();
        if ($request) {
            $this->clientListQueryFilter->apply($qb, $request);
        }

        $qb->addSelect('createdBy');

        return $qb;
    }

    #[AdminRoute(path: '/email-export', name: 'email_export', options: ['methods' => ['GET']])]
    #[IsGranted('ROLE_USER')]
    public function emailExport(AdminContext $context): JsonResponse
    {
        if (!$this->isGranted(Permission::EA_EXECUTE_ACTION, [
            'action' => 'emailExport',
            'entity' => null,
            'entityFqcn' => $context->getEntity()->getFqcn(),
        ])) {
            throw new ForbiddenActionException($context);
        }

        $crud = $context->getCrud();
        $search = $context->getSearch();
        if ($crud === null || $search === null) {
            throw new \LogicException('Email export requires an EasyAdmin CRUD context.');
        }

        $fields = new FieldCollection($this->configureFields(Crud::PAGE_INDEX));
        $filters = $this->container->get(FilterFactory::class)->create(
            $crud->getFiltersConfig(),
            $fields,
            $context->getEntity(),
        );
        $qb = $this->createIndexQueryBuilder(
            $search,
            $context->getEntity(),
            $fields,
            $filters,
        );

        $pageSize = $crud->getPaginator()?->getPageSize() ?? 25;

        return $this->json($this->clientEmailExporter->export($qb, $context->getRequest(), $pageSize));
    }

    public static function getEntityFqcn(): string
    {
        return Client::class;
    }

    public function configureAssets(Assets $assets): Assets
    {
        return $assets
            ->addCssFile('css/admin_nip.css');
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Klient')
            ->setEntityLabelInPlural('Klienci')
            ->setSearchFields([
                'nip',
                'companyName',
                'phone',
                'email',
                'street',
                'city',
                'postalCode',
                'turnover',
                'notes',
                'industries.name',
                'assortments.name',
                'sources.name',
            ])
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->setPaginatorPageSize(25)
            ->showEntityActionsInlined()
            ->overrideTemplate('crud/index', 'admin/client_index.html.twig')
            ->overrideTemplate('crud/detail', 'admin/client_detail.html.twig')
            ->overrideTemplate('crud/new', 'admin/client_new.html.twig')
            ->overrideTemplate('crud/edit', 'admin/client_edit.html.twig');
    }

    protected function getRedirectResponseAfterSave(\EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext $context, string $action): \Symfony\Component\HttpFoundation\RedirectResponse
    {
        $requestAll = $context->getRequest()->request->all();
        $submitButtonName = $requestAll['ea']['newForm']['btn']
            ?? $requestAll['ea']['editForm']['btn']
            ?? 'saveAndReturn';

        if ('saveAndGoTasks' === $submitButtonName) {
            $savedEntity = $context->getEntity()->getInstance();
            $urlGenerator = $this->container->get(AdminUrlGenerator::class);
            $url = $urlGenerator
                ->setController(TaskCrudController::class)
                ->setAction(Action::NEW)
                ->setEntityId(null)
                ->set('clientId', $savedEntity?->getId())
                ->generateUrl();

            return $this->redirect($url);
        }

        if ('saveAndReturn' === $submitButtonName) {
            $referer = $context->getReferrer();
            if ($referer !== null && $referer !== '') {
                return $this->redirect($referer);
            }

            $url = $this->container->get(AdminUrlGenerator::class)
                ->setAction(Action::INDEX)
                ->setEntityId(null)
                ->generateUrl();

            return $this->redirect($url);
        }

        return parent::getRedirectResponseAfterSave($context, $action);
    }

    public function configureResponseParameters(KeyValueStore $responseParameters): KeyValueStore
    {
        if (Crud::PAGE_INDEX === $responseParameters->get('pageName')) {
            $responseParameters->set('industryFilterOptions', $this->getEntityNameOptions(Industry::class));
            $responseParameters->set('assortmentFilterOptions', $this->getEntityNameOptions(Assortment::class));
            $responseParameters->set('sourceFilterOptions', $this->getEntityNameOptions(Source::class));
            $responseParameters->set('createdByFilterOptions', $this->getUserFilterOptions());
        }

        if (Crud::PAGE_EDIT === $responseParameters->get('pageName')) {
            $form = $this->createForm(QuickTaskType::class, new Task());
            $responseParameters->set('quickTaskForm', $form->createView());

            $completedForm = $this->createForm(QuickCompletedTaskType::class, new Task());
            $responseParameters->set('quickCompletedTaskForm', $completedForm->createView());
        }

        return $responseParameters;
    }

    private function getEntityNameOptions(string $entityClass): array
    {
        $options = [];
        $items = $this->em->getRepository($entityClass)->findBy([], ['name' => 'ASC']);

        foreach ($items as $item) {
            if (!method_exists($item, 'getName')) {
                continue;
            }

            $name = $item->getName();
            if ($name !== null && $name !== '') {
                $options[$name] = $name;
            }
        }

        return $options;
    }

    private function getUserFilterOptions(): array
    {
        $options = [];
        $users = $this->em->getRepository(User::class)->findBy([], [
            'lastName' => 'ASC',
            'firstName' => 'ASC',
        ]);

        foreach ($users as $user) {
            $id = $user->getId();
            if ($id === null) {
                continue;
            }

            $options[$id] = $user->getFullName();
        }

        return $options;
    }

    public function configureFields(string $pageName): iterable
    {
        yield FormField::addColumn(12);

        yield IdField::new('id', 'ID')
            ->hideOnForm();

        yield FormField::addPanel('Dane firmy')
            ->setIcon('fa fa-building')
            ->collapsible(false)
            ->addCssClass('form-panel-company');

        yield TextField::new('nip', 'NIP')
            ->setColumns(5)
            ->setRequired(false)
            ->setHelp('')
            ->setFormTypeOption('attr', ['id' => 'cli_nip_input', 'autocomplete' => 'off'])
            ->formatValue(function ($value) {
                return empty($value) ? 'Działalność nierejestrowana' : $value;
            });

        yield TextField::new('companyName', 'Nazwa firmy')
            ->setColumns(7);

        yield FormField::addPanel('Kontakt')
            ->setIcon('fa fa-address-card')
            ->collapsible(false)
            ->addCssClass('form-panel-contact');

        yield TelephoneField::new('phone', 'Telefon')
            ->setColumns(4);

        yield EmailField::new('email', 'E-mail')
            ->setColumns(4);

        yield AssociationField::new('contactMethods', 'Preferowany kontakt')
            ->setColumns(4)
            ->setQueryBuilder(fn($qb) => $qb->orderBy('entity.id', 'ASC'));

        yield FormField::addColumn(6);

        yield FormField::addPanel('Informacje biznesowe')
            ->setIcon('fa fa-briefcase')
            ->collapsible(false)
            ->addCssClass('form-panel-business');

        yield AssociationField::new('industries', 'Branże')
            ->setColumns(12)
            ->autocomplete()
            ->setCrudController(IndustryCrudController::class)
            ->setFormTypeOptions([
                'by_reference' => false,
            ])
            ->setFormTypeOption('attr.data-ea-autocomplete-allow-item-create', 'true')
            ->setHelp('Wpisz branżę lub wybierz z listy. Możesz wybrać kilka.');

        yield AssociationField::new('assortments', 'Asortyment')
            ->setColumns(12)
            ->autocomplete()
            ->setCrudController(AssortmentCrudController::class)
            ->setFormTypeOptions([
                'by_reference' => false,
            ])
            ->setFormTypeOption('attr.data-ea-autocomplete-allow-item-create', 'true')
            ->setHelp('Wpisz asortyment lub wybierz z listy. Możesz wybrać kilka.');

        yield AssociationField::new('sources', 'Źródła pozyskania')
            ->setColumns(12)
            ->autocomplete()
            ->setCrudController(SourceCrudController::class)
            ->setFormTypeOptions([
                'by_reference' => false,
            ])
            ->setFormTypeOption('attr.data-ea-autocomplete-allow-item-create', 'true')
            ->setHelp('Wpisz źródło lub wybierz z listy. Możesz wybrać kilka.');

        yield NumberField::new('turnover', 'Obroty (PLN)')
            ->setColumns(6)
            ->setNumDecimals(2)
            ->setHelp('Obroty klienta w PLN');

        yield FormField::addPanel('Sugestia klienta')
            ->setIcon('fa fa-lightbulb')
            ->collapsible(false)
            ->addCssClass('form-panel-suggestion')
            ->onlyOnForms();

        yield TextField::new('newSuggestionTitle', 'Tytuł sugestii')
            ->setColumns(12)
            ->onlyOnForms()
            ->setHelp('Krótki tytuł sugestii (opcjonalnie).');

        yield TextareaField::new('newSuggestionText', 'Treść sugestii')
            ->setColumns(12)
            ->setMaxLength(1000)
            ->onlyOnForms()
            ->setHelp('Treść sugestii klienta. Zostanie zapisana w zakładce "Sugestie" – nie będzie widoczna w karcie klienta.');

        yield AssociationField::new('suggestionDraftTags', 'Tagi sugestii')
            ->setColumns(12)
            ->autocomplete()
            ->setCrudController(TagCrudController::class)
            ->setFormTypeOptions(['by_reference' => false])
            ->setFormTypeOption('attr.data-ea-autocomplete-allow-item-create', 'true')
            ->onlyOnForms()
            ->setHelp('Wybierz istniejące tagi lub wpisz nowy i naciśnij Enter. Zostaną przypisane do sugestii.');

        yield FormField::addColumn(6);

        yield FormField::addPanel('Status klienta')
            ->setIcon('fa fa-tags')
            ->collapsible(false)
            ->addCssClass('form-panel-status');

        yield BooleanField::new('hasBusinessAccount', 'Posiada konto biznesowe')
            ->setColumns(12)
            ->renderAsSwitch(true);

        yield BooleanField::new('hasPurchased', 'Dokonał zakupu')
            ->setColumns(12)
            ->renderAsSwitch(true);

        yield BooleanField::new('isLocalClient', 'Klient lokalny')
            ->setColumns(12)
            ->renderAsSwitch(true)
            ->setHelp('Ustawiane automatycznie na podstawie kodu pocztowego (≤150 km od Garwolina)')
            ->hideOnForm();

        yield FormField::addPanel('Adres firmy')
            ->setIcon('fa fa-map-marker-alt')
            ->collapsible(false)
            ->addCssClass('form-panel-address');

        yield TextField::new('street', 'Ulica i numer')
            ->setColumns(12)
            ->setHelp('np. ul. Długa 15/2');

        yield TextField::new('city', 'Miasto')
            ->setColumns(12);

        yield TextField::new('postalCode', 'Kod pocztowy')
            ->setColumns(12)
            ->setHelp('Format: XX-XXX. Na tej podstawie automatycznie ustalimy czy klient jest lokalny.');

        yield FormField::addPanel('Uwagi')
            ->setIcon('fa fa-sticky-note')
            ->collapsible(false)
            ->addCssClass('form-panel-notes');

        yield TextareaField::new('notes', 'Dodatkowe uwagi')
            ->setColumns(12)
            ->setMaxLength(50)
            ->renderAsHtml();

        yield DateTimeField::new('createdAt', 'Utworzono')
            ->hideOnForm()
            ->hideOnIndex()
            ->setFormat('dd.MM.yyyy HH:mm');

        yield DateTimeField::new('updatedAt', 'Zaktualizowano')
            ->hideOnForm()
            ->hideOnIndex()
            ->setFormat('dd.MM.yyyy HH:mm');
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('companyName', 'Nazwa firmy'))
            ->add(TextFilter::new('nip', 'NIP'))
            ->add(EntityFilter::new('industries', 'Branża'))
            ->add(EntityFilter::new('assortments', 'Asortyment'))
            ->add(BooleanFilter::new('hasBusinessAccount', 'Konto biznesowe'))
            ->add(BooleanFilter::new('hasPurchased', 'Kupił'))
            ->add(BooleanFilter::new('isLocalClient', 'Klient lokalny'))
            ->add(EntityFilter::new('contactMethods', 'Forma kontaktu'))
            ->add(EntityFilter::new('sources', 'Źródło'));
    }

    public function configureActions(Actions $actions): Actions
    {
        $emailExport = Action::new('emailExport', false, 'fa fa-envelope')
            ->linkToCrudAction('emailExport')
            ->createAsGlobalAction()
            ->displayIf(static fn () => false);

        $actions->add(Crud::PAGE_INDEX, $emailExport);
        $actions->setPermission('emailExport', 'ROLE_USER');

        $actions->update(Crud::PAGE_INDEX, Action::DELETE, fn(Action $a) => $a
            ->setLabel('Przenieś do kosza')
            ->setIcon('fa fa-trash')
            ->setCssClass('text-danger action-delete')
        );
        $actions->update(Crud::PAGE_DETAIL, Action::DELETE, fn(Action $a) => $a
            ->setLabel('Przenieś do kosza')
            ->setIcon('fa fa-trash')
            ->setCssClass('text-danger action-delete')
        );

        $actions->disable(Action::BATCH_DELETE);

        $actions->reorder(Crud::PAGE_INDEX, [Action::DETAIL, Action::EDIT, Action::DELETE]);
        $actions->reorder(Crud::PAGE_DETAIL, [Action::EDIT, Action::DELETE, Action::INDEX]);

        if ($this->security->isGranted('ROLE_VIEWER') && !$this->security->isGranted('ROLE_USER')) {
            $actions->disable(Action::NEW, Action::EDIT, Action::DELETE);
        } elseif (!$this->security->isGranted('ROLE_ADMIN')) {
            $actions->disable(Action::DELETE);
        }

        return $actions;
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $user = $this->security->getUser();
        if ($entityInstance->getCreatedBy() === null && $user instanceof User) {
            $entityInstance->setCreatedBy($user);
        }

        parent::persistEntity($entityManager, $entityInstance);
    }

    public function deleteEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {

        $entityInstance->setDeletedAt(new \DateTimeImmutable());
        $entityManager->flush();
    }
}

