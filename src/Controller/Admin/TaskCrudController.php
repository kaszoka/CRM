<?php

namespace App\Controller\Admin;

use App\Entity\Client;
use App\Entity\Task;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class TaskCrudController extends AbstractCrudController
{
    public function __construct(
        private Security $security,
        private EntityManagerInterface $em,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return Task::class;
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $qb = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);

        $request = $this->container->get('request_stack')->getCurrentRequest();
        $myTasks = $request?->query->get('myTasks', '1');
        $poolTasks = $request?->query->get('pool', '0');

        $user = $this->security->getUser();

        if ($myTasks === '1' && $poolTasks !== '1' && $user) {

            $qb->andWhere('entity.assignedTo = :currentUser')
               ->setParameter('currentUser', $user);
        } elseif ($poolTasks === '1' && $myTasks !== '1') {

            $qb->andWhere('entity.assignedTo IS NULL');
        } elseif ($poolTasks === '1' && $myTasks === '1' && $user) {

            $qb->andWhere('(entity.assignedTo = :currentUser OR entity.assignedTo IS NULL)')
               ->setParameter('currentUser', $user);
        }

        return $qb;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Zadanie')
            ->setEntityLabelInPlural('Zadania')
            ->setSearchFields(['title', 'description'])
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->setPaginatorPageSize(50)
            ->showEntityActionsInlined()
            ->overrideTemplate('crud/index', 'admin/task_index.html.twig')
            ->overrideTemplate('crud/detail', 'admin/task_detail.html.twig');
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')
            ->hideOnForm();

        yield FormField::addPanel('Zadanie')
            ->setIcon('fa fa-clipboard')
            ->collapsible(false)
            ->addCssClass('form-panel-task');

        yield TextField::new('title', 'Tytuł')
            ->setColumns(12);

        yield TextareaField::new('description', 'Opis')
            ->setColumns(12)
            ->hideOnIndex();

        yield FormField::addPanel('Szczegóły')
            ->setIcon('fa fa-sliders')
            ->collapsible(false)
            ->addCssClass('form-panel-task-details');

        yield ChoiceField::new('priority', 'Priorytet')
            ->setChoices(Task::PRIORITIES)
            ->setColumns(4)
            ->renderAsBadges([
                Task::PRIORITY_HIGH => 'danger',
                Task::PRIORITY_MEDIUM => 'warning',
                Task::PRIORITY_LOW => 'success',
            ]);

        yield ChoiceField::new('status', 'Status')
            ->setChoices(Task::STATUSES)
            ->setColumns(4)
            ->renderAsBadges([
                Task::STATUS_NEW => 'info',
                Task::STATUS_IN_PROGRESS => 'warning',
                Task::STATUS_COMPLETED => 'success',
            ]);

        yield AssociationField::new('assignedTo', 'Przypisano do')
            ->setColumns(4);

        yield AssociationField::new('createdBy', 'Dodane przez')
            ->hideOnForm()
            ->setColumns(4);

        yield FormField::addPanel('Powiązanie')
            ->setIcon('fa fa-link')
            ->collapsible(false)
            ->addCssClass('form-panel-task-link');

        if (Crud::PAGE_NEW === $pageName) {
            yield AssociationField::new('client', 'Klienci')
                ->setColumns(6)
                ->setQueryBuilder(fn(QueryBuilder $qb) => $qb
                    ->andWhere('entity.deletedAt IS NULL')
                    ->orderBy('entity.createdAt', 'DESC')
                )
                ->setFormTypeOption('multiple', true)
                ->setFormTypeOption('mapped', false)
                ->setFormTypeOption('required', true)
                ->setFormTypeOption('data', $this->getPreselectedClients())
                ->setHelp('Wybierz jednego albo wielu klientów. Dla każdego wybranego klienta powstanie osobne zadanie.');
        } else {
            yield AssociationField::new('client', 'Klient')
                ->setQueryBuilder(fn(QueryBuilder $qb) => $qb
                    ->andWhere('entity.deletedAt IS NULL')
                    ->orderBy('entity.createdAt', 'DESC')
                )
                ->setColumns(6);
        }

        yield DateField::new('dueDate', 'Termin')
            ->setColumns(6)
            ->setFormat('dd.MM.yyyy');

        yield DateTimeField::new('createdAt', 'Utworzono')
            ->hideOnForm()
            ->setFormat('dd.MM.yyyy HH:mm');

        yield DateTimeField::new('updatedAt', 'Zaktualizowano')
            ->hideOnForm()
            ->hideOnIndex()
            ->setFormat('dd.MM.yyyy HH:mm');
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(ChoiceFilter::new('priority', 'Priorytet')
                ->setChoices(Task::PRIORITIES))
            ->add(ChoiceFilter::new('status', 'Status')
                ->setChoices(Task::STATUSES))
            ->add(EntityFilter::new('assignedTo', 'Przypisano do'))
            ->add(EntityFilter::new('client', 'Klient'))
            ->add(DateTimeFilter::new('dueDate', 'Termin'));
    }

    public function configureActions(Actions $actions): Actions
    {
        $actions->reorder(Crud::PAGE_INDEX, [Action::DETAIL, Action::EDIT, Action::DELETE]);

        if (!$this->security->isGranted('ROLE_ADMIN')) {
            $actions->disable(Action::DELETE, Action::BATCH_DELETE);
        }

        return $actions;
    }

    public function createEntity(string $entityFqcn): Task
    {
        $task = new Task();

        $request = $this->container->get('request_stack')->getCurrentRequest();
        $clientId = $request?->query->get('clientId');
        if ($clientId) {
            $client = $this->em->getRepository(Client::class)->find((int) $clientId);
            if ($client) {
                $task->setClient($client);
            }
        }

        return $task;
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance->getCreatedBy() === null) {
            $entityInstance->setCreatedBy($this->security->getUser());
        }

        $clients = $this->getSubmittedClients();
        if ($clients === []) {
            if ($entityInstance->getClient() !== null) {
                $clients[] = $entityInstance->getClient();
            } else {
                $this->addFlash('danger', 'Wybierz co najmniej jednego klienta.');
                return;
            }
        }

        $firstClient = array_shift($clients);
        $entityInstance->setClient($firstClient);
        $entityManager->persist($entityInstance);

        foreach ($clients as $client) {
            $entityManager->persist($this->copyTaskForClient($entityInstance, $client));
        }

        $entityManager->flush();

        $createdCount = 1 + \count($clients);
        if ($createdCount > 1) {
            $this->addFlash('success', sprintf('Utworzono %d zadań dla wybranych klientów.', $createdCount));
        }
    }

    private function getPreselectedClients(): array
    {
        $request = $this->container->get('request_stack')->getCurrentRequest();
        $clientId = $request?->query->get('clientId');
        if (!$clientId) {
            return [];
        }

        $client = $this->em->getRepository(Client::class)->find((int) $clientId);

        return $client instanceof Client ? [$client] : [];
    }

    private function getSubmittedClients(): array
    {
        $request = $this->container->get('request_stack')->getCurrentRequest();
        $data = $request?->request->all('Task') ?? [];
        $clientIds = $data['client'] ?? [];
        if (!\is_array($clientIds)) {
            $clientIds = $clientIds !== '' ? [$clientIds] : [];
        }

        $clientIds = array_values(array_unique(array_filter(array_map('intval', $clientIds))));
        if ($clientIds === []) {
            return [];
        }

        $clients = $this->em->getRepository(Client::class)
            ->createQueryBuilder('c')
            ->andWhere('c.id IN (:ids)')
            ->andWhere('c.deletedAt IS NULL')
            ->setParameter('ids', $clientIds)
            ->getQuery()
            ->getResult();
        $clientsById = [];
        foreach ($clients as $client) {
            $clientsById[$client->getId()] = $client;
        }

        $orderedClients = [];
        foreach ($clientIds as $clientId) {
            if (isset($clientsById[$clientId])) {
                $orderedClients[] = $clientsById[$clientId];
            }
        }

        return $orderedClients;
    }

    private function copyTaskForClient(Task $source, Client $client): Task
    {
        $task = new Task();
        $task->setTitle((string) $source->getTitle());
        $task->setDescription($source->getDescription());
        $task->setPriority((string) $source->getPriority());
        $task->setStatus((string) $source->getStatus());
        $task->setAssignedTo($source->getAssignedTo());
        $task->setCreatedBy($source->getCreatedBy());
        $task->setDueDate($source->getDueDate());
        $task->setClient($client);

        return $task;
    }
}

