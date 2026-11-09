<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Client;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TelephoneField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
final class ClientTrashCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return Client::class;
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $qb = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);

        $qb->andWhere('entity.deletedAt IS NOT NULL');

        return $qb;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Klient (kosz)')
            ->setEntityLabelInPlural('Kosz – usunięci klienci')
            ->setSearchFields(['nip', 'companyName', 'phone', 'email'])
            ->setDefaultSort(['deletedAt' => 'DESC'])
            ->setPaginatorPageSize(25)
            ->showEntityActionsInlined();
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')->hideOnForm();
        yield TextField::new('companyName', 'Nazwa firmy');
        yield TextField::new('nip', 'NIP')
            ->formatValue(fn($v) => $v ?? 'Działalność nierejestrowana');
        yield TelephoneField::new('phone', 'Telefon');
        yield EmailField::new('email', 'E-mail');
        yield DateTimeField::new('deletedAt', 'Usunięto')
            ->setFormat('dd.MM.yyyy HH:mm')
            ->hideOnForm();
        yield DateTimeField::new('createdAt', 'Dodano')
            ->setFormat('dd.MM.yyyy HH:mm')
            ->hideOnForm();
    }

    public function configureActions(Actions $actions): Actions
    {
        $restore = Action::new('restore', 'Przywróć', 'fa fa-rotate-left')
            ->linkToCrudAction('restore')
            ->setCssClass('text-success');

        $actions->update(Crud::PAGE_INDEX, Action::DELETE, function (Action $action) {
            return $action
                ->setLabel('Usuń na stałe')
                ->setIcon('fa fa-times')
                ->setCssClass('text-danger action-delete');
        });

        $actions->disable(
            Action::NEW,
            Action::EDIT,
            Action::BATCH_DELETE,
            Action::DETAIL,
            Action::SAVE_AND_RETURN,
            Action::SAVE_AND_ADD_ANOTHER,
            Action::SAVE_AND_CONTINUE,
        );

        $actions->add(Crud::PAGE_INDEX, $restore);

        return $actions;
    }

    public function restore(AdminContext $context): RedirectResponse
    {

        $client = $context->getEntity()->getInstance();
        $client->setDeletedAt(null);
        $this->em->flush();

        $this->addFlash('success', sprintf('Klient „%s" został przywrócony.', $client->getCompanyName()));

        return $this->redirect(
            $this->container->get(AdminUrlGenerator::class)
                ->setController(self::class)
                ->setAction(Action::INDEX)
                ->generateUrl()
        );
    }
}

