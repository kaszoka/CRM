<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\ContactMethod;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class ContactMethodCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ContactMethod::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Forma kontaktu')
            ->setEntityLabelInPlural('Formy kontaktu')
            ->setDefaultSort(['id' => 'ASC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')->hideOnForm();
        yield TextField::new('name', 'Nazwa');
        yield TextField::new('slug', 'Slug (identyfikator)')
            ->setHelp('Opcjonalny identyfikator. Ustaw "nie_kontaktowac" aby klient wyświetlał się na czerwono.')
            ->setRequired(false);
    }
}

