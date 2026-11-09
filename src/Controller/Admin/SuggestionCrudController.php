<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Suggestion;
use App\Repository\TagRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class SuggestionCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly Security $security,
        private readonly RequestStack $requestStack,
        private readonly TagRepository $tagRepository,
    ) {}

    public static function getEntityFqcn(): string
    {
        return Suggestion::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Sugestia')
            ->setEntityLabelInPlural('Sugestie')
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->setPaginatorPageSize(25)
            ->setSearchFields(['title', 'content', 'client.companyName', 'client.nip'])
            ->overrideTemplate('crud/index', 'admin/suggestion_index.html.twig')
            ->overrideTemplate('crud/detail', 'admin/suggestion_detail.html.twig');
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $qb = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);

        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return $qb;
        }

        $tagId = $request->query->get('f_tag');
        if ($tagId !== null && $tagId !== '') {
            $qb->leftJoin('entity.tags', 'ft')
               ->andWhere('ft.id = :tagId')
               ->setParameter('tagId', (int) $tagId);
        }

        return $qb;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')->hideOnForm();

        yield FormField::addPanel('Sugestia')
            ->setIcon('fa fa-lightbulb')
            ->onlyOnForms();

        yield TextField::new('title', 'Tytuł')
            ->setColumns(12);

        yield TextareaField::new('content', 'Treść sugestii')
            ->setColumns(12)
            ->hideOnIndex();

        yield FormField::addPanel('Klient i tagi')
            ->setIcon('fa fa-tags')
            ->onlyOnForms();

        yield AssociationField::new('client', 'Klient')
            ->autocomplete()
            ->setCrudController(ClientCrudController::class)
            ->setColumns(12)
            ->hideOnIndex();

        yield AssociationField::new('tags', 'Tagi')
            ->setFormTypeOptions(['by_reference' => false])
            ->autocomplete()
            ->setCrudController(TagCrudController::class)
            ->setFormTypeOption('attr.data-ea-autocomplete-allow-item-create', 'true')
            ->setColumns(12)
            ->setHelp('Wybierz istniejące tagi lub wpisz nowy i naciśnij Enter, aby go utworzyć.')
            ->hideOnIndex();

        yield AssociationField::new('createdBy', 'Dodane przez')
            ->hideOnForm();

        yield DateTimeField::new('createdAt', 'Data dodania')
            ->hideOnForm()
            ->setFormat('dd.MM.yyyy HH:mm');
    }

    public function configureActions(Actions $actions): Actions
    {
        if (!$this->security->isGranted('ROLE_ADMIN')) {
            $actions->disable(Action::DELETE, Action::BATCH_DELETE);
        }

        return $actions;
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance->getCreatedBy() === null) {
            $entityInstance->setCreatedBy($this->security->getUser());
        }

        parent::persistEntity($entityManager, $entityInstance);
    }
}

