<?php

namespace App\Controller\Admin;

use App\Entity\ActivityLog;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class ActivityLogCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ActivityLog::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Log')
            ->setEntityLabelInPlural('Logi aktywności')
            ->setSearchFields(['entityName', 'description', 'entityType', 'action'])
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->setPaginatorPageSize(50)
            ->showEntityActionsInlined()
            ->overrideTemplate('crud/index', 'admin/log_index.html.twig');
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')->hideOnForm();
        yield TextField::new('actionLabel', 'Akcja');
        yield TextField::new('entityTypeLabel', 'Typ');
        yield TextField::new('entityName', 'Nazwa');
        yield TextField::new('description', 'Opis');
        yield AssociationField::new('user', 'Użytkownik');
        yield DateTimeField::new('createdAt', 'Data')->setFormat('dd.MM.yyyy HH:mm:ss');
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(ChoiceFilter::new('action', 'Akcja')->setChoices(array_flip(ActivityLog::ACTION_LABELS)))
            ->add(ChoiceFilter::new('entityType', 'Typ')->setChoices(array_flip(ActivityLog::ENTITY_LABELS)))
            ->add(EntityFilter::new('user', 'Użytkownik'))
            ->add(DateTimeFilter::new('createdAt', 'Data'));
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable(Action::NEW, Action::EDIT, Action::DELETE, Action::BATCH_DELETE);
    }
}

