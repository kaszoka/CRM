<?php

namespace App\Controller\Admin;

use App\Controller\Admin\ClientCrudController;
use App\Controller\Admin\ClientTrashCrudController;
use App\Controller\Admin\ContactMethodCrudController;
use App\Entity\ActivityLog;
use App\Entity\Assortment;
use App\Entity\Client;
use App\Entity\Industry;
use App\Entity\Source;
use App\Entity\Suggestion;
use App\Entity\Tag;
use App\Entity\Task;
use App\Entity\User;
use App\Repository\ClientRepository;
use App\Repository\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\UserMenu;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class DashboardController extends AbstractDashboardController
{
    public function __construct(
        private EntityManagerInterface $em,
        private Security $security,
    ) {
    }

    #[Route('/admin', name: 'admin')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function index(): Response
    {
        $user = $this->security->getUser();

        if ($user instanceof \App\Entity\User && $user->mustChangePassword()) {
            return $this->redirectToRoute('app_change_password');
        }

        if ($this->security->isGranted('ROLE_VIEWER') && !$this->security->isGranted('ROLE_USER')) {
            $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);
            return $this->redirect(
                $adminUrlGenerator->setController(ClientCrudController::class)->setAction(Action::INDEX)->generateUrl()
            );
        }

        $totalClients = $this->em->getRepository(Client::class)->count([]);
        $clientsWithAccount = $this->em->getRepository(Client::class)->count(['hasBusinessAccount' => true]);
        $clientsPurchased = $this->em->getRepository(Client::class)->count(['hasPurchased' => true]);

        $totalTasks = $this->em->getRepository(Task::class)->count([]);
        $myTasks = $this->em->getRepository(Task::class)->count(['assignedTo' => $user]);
        $myTasksNew = $this->em->getRepository(Task::class)->count(['assignedTo' => $user, 'status' => Task::STATUS_NEW]);
        $myTasksInProgress = $this->em->getRepository(Task::class)->count(['assignedTo' => $user, 'status' => Task::STATUS_IN_PROGRESS]);
        $highPriorityTasks = $this->em->getRepository(Task::class)->count(['assignedTo' => $user, 'priority' => Task::PRIORITY_HIGH, 'status' => Task::STATUS_NEW])
            + $this->em->getRepository(Task::class)->count(['assignedTo' => $user, 'priority' => Task::PRIORITY_HIGH, 'status' => Task::STATUS_IN_PROGRESS]);

        $recentTasks = $this->em->getRepository(Task::class)->createQueryBuilder('t')
            ->andWhere('t.assignedTo = :user')
            ->andWhere('t.status != :completed')
            ->setParameter('user', $user)
            ->setParameter('completed', Task::STATUS_COMPLETED)
            ->addOrderBy('t.priority', 'ASC')
            ->addOrderBy('t.dueDate', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        $recentClients = $this->em->getRepository(Client::class)->createQueryBuilder('c')
            ->orderBy('c.createdAt', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);

        $taskDetailUrls = [];
        foreach ($recentTasks as $task) {
            $taskDetailUrls[$task->getId()] = $adminUrlGenerator
                ->setController(TaskCrudController::class)
                ->setAction(Action::DETAIL)
                ->setEntityId($task->getId())
                ->generateUrl();
        }

        $clientDetailUrls = [];
        foreach ($recentClients as $client) {
            $clientDetailUrls[$client->getId()] = $adminUrlGenerator
                ->setController(ClientCrudController::class)
                ->setAction(Action::DETAIL)
                ->setEntityId($client->getId())
                ->generateUrl();
        }

        return $this->render('admin/dashboard.html.twig', [
            'user' => $user,
            'totalClients' => $totalClients,
            'clientsWithAccount' => $clientsWithAccount,
            'clientsPurchased' => $clientsPurchased,
            'totalTasks' => $totalTasks,
            'myTasks' => $myTasks,
            'myTasksNew' => $myTasksNew,
            'myTasksInProgress' => $myTasksInProgress,
            'highPriorityTasks' => $highPriorityTasks,
            'recentTasks' => $recentTasks,
            'recentClients' => $recentClients,
            'taskDetailUrls' => $taskDetailUrls,
            'clientDetailUrls' => $clientDetailUrls,
            'clientsUrl' => $adminUrlGenerator->setController(ClientCrudController::class)->setAction(Action::INDEX)->generateUrl(),
            'newClientUrl' => $adminUrlGenerator->setController(ClientCrudController::class)->setAction(Action::NEW)->generateUrl(),
            'tasksUrl' => $adminUrlGenerator->setController(TaskCrudController::class)->setAction(Action::INDEX)->generateUrl(),
            'newTaskUrl' => $adminUrlGenerator->setController(TaskCrudController::class)->setAction(Action::NEW)->generateUrl(),
        ]);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('<img src="/pwpola-crm-logo.png" width="32"> <span>Pola CRM</span>')
            ->setFaviconPath('pwpola-crm-logo.png')
            ->setLocales(['pl'])
            ->renderContentMaximized();
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Panel główny', 'fa fa-home')
            ->setPermission('ROLE_USER');

        yield MenuItem::section('CRM');
        yield MenuItem::linkToCrud('Klienci', 'fa fa-building', Client::class)
            ->setController(ClientCrudController::class)
            ->setDefaultSort(['createdAt' => 'DESC']);
        yield MenuItem::linkToCrud('Zadania', 'fa fa-tasks', Task::class)
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->setPermission('ROLE_USER');
        yield MenuItem::linkToCrud('Sugestie', 'fa fa-lightbulb', Suggestion::class)
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->setPermission('ROLE_USER');
        yield MenuItem::linkToCrud('Logi', 'fa fa-history', ActivityLog::class)
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->setPermission('ROLE_USER');

        yield MenuItem::section('Zarządzanie')
            ->setPermission('ROLE_ADMIN');
        yield MenuItem::linkToCrud('Kosz klientów', 'fa fa-trash', Client::class)
            ->setController(ClientTrashCrudController::class)
            ->setPermission('ROLE_ADMIN');
        yield MenuItem::linkToCrud('Branże', 'fa fa-briefcase', Industry::class)
            ->setPermission('ROLE_ADMIN');
        yield MenuItem::linkToCrud('Asortyment', 'fa fa-boxes-stacked', Assortment::class)
            ->setPermission('ROLE_ADMIN');
        yield MenuItem::linkToCrud('Źródła', 'fa fa-bullhorn', Source::class)
            ->setPermission('ROLE_ADMIN');
        yield MenuItem::linkToCrud('Tagi', 'fa fa-tags', Tag::class)
            ->setPermission('ROLE_ADMIN');
        yield MenuItem::linkToCrud('Formy kontaktu', 'fa fa-address-book', \App\Entity\ContactMethod::class)
            ->setController(ContactMethodCrudController::class)
            ->setPermission('ROLE_ADMIN');

        yield MenuItem::section('Administracja');
        yield MenuItem::linkToCrud('Użytkownicy', 'fa fa-users', User::class)
            ->setPermission('ROLE_ADMIN');
        yield MenuItem::linkToRoute('2FA dla mnie', 'fa fa-shield', 'app_2fa_settings');
    }

    public function configureUserMenu(UserInterface $user): UserMenu
    {
        return parent::configureUserMenu($user)
            ->setName($user->getUserIdentifier())
            ->setGravatarEmail($user->getUserIdentifier());
    }

    public function configureAssets(): Assets
    {
        return Assets::new()
            ->addCssFile('css/tabler-theme.css?v=5');
    }

    public function configureCrud(): Crud
    {
        return Crud::new()
            ->setDateFormat('dd.MM.yyyy')
            ->setDateTimeFormat('dd.MM.yyyy HH:mm')
            ->setTimezone('Europe/Warsaw')
            ->setPaginatorPageSize(25)
            ->showEntityActionsInlined();
    }

    public function configureActions(): Actions
    {
        return Actions::new()
            ->addBatchAction(Action::BATCH_DELETE)
            ->add(Crud::PAGE_INDEX, Action::NEW)
            ->add(Crud::PAGE_INDEX, Action::EDIT)
            ->add(Crud::PAGE_INDEX, Action::DELETE)
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_DETAIL, Action::EDIT)
            ->add(Crud::PAGE_DETAIL, Action::DELETE)
            ->add(Crud::PAGE_DETAIL, Action::INDEX)
            ->add(Crud::PAGE_EDIT, Action::SAVE_AND_RETURN)
            ->add(Crud::PAGE_EDIT, Action::SAVE_AND_CONTINUE)
            ->add(Crud::PAGE_NEW, Action::SAVE_AND_RETURN)
            ->add(Crud::PAGE_NEW, Action::SAVE_AND_ADD_ANOTHER);
    }
}

