<?php

namespace App\Controller\Admin;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Constraints as Assert;

#[IsGranted('ROLE_ADMIN')]
class UserCrudController extends AbstractCrudController
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Użytkownik')
            ->setEntityLabelInPlural('Użytkownicy')
            ->setSearchFields(['email', 'firstName', 'lastName'])
            ->setDefaultSort(['createdAt' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        $passwordConstraints = [
            new Assert\Length(
                min: 8,
                minMessage: 'Hasło musi mieć co najmniej {{ limit }} znaków.'
            ),
            new Assert\Regex(
                pattern: '/(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^\w\s]).+/',
                message: 'Hasło musi zawierać małe i wielkie litery, cyfrę oraz znak specjalny.'
            ),
        ];

        if ($pageName === Crud::PAGE_NEW) {
            array_unshift(
                $passwordConstraints,
                new Assert\NotBlank(message: 'Hasło jest wymagane.')
            );
        }

        yield IdField::new('id', 'ID')
            ->hideOnForm();

        yield TextField::new('firstName', 'Imię')
            ->setColumns(6);

        yield TextField::new('lastName', 'Nazwisko')
            ->setColumns(6);

        yield EmailField::new('email', 'E-mail')
            ->setColumns(6);

        yield TextField::new('password', 'Hasło')
            ->setFormType(RepeatedType::class)
            ->setFormTypeOptions([
                'type' => PasswordType::class,
                'first_options' => [
                    'label' => 'Hasło',
                    'attr' => ['autocomplete' => 'new-password'],
                ],
                'second_options' => [
                    'label' => 'Powtórz hasło',
                    'attr' => ['autocomplete' => 'new-password'],
                ],
                'mapped' => false,
                'required' => $pageName === Crud::PAGE_NEW,
                'constraints' => $passwordConstraints,
            ])
            ->setColumns(6)
            ->setHelp('Hasło musi mieć min. 8 znaków i zawierać małe i wielkie litery, cyfrę oraz znak specjalny – takie są minimalne wytyczne bezpieczeństwa.')
            ->onlyOnForms();

        yield ChoiceField::new('roles', 'Role')
            ->setChoices([
                'Administrator' => 'ROLE_ADMIN',
                'Użytkownik' => 'ROLE_USER',
                'Przeglądający (tylko odczyt)' => 'ROLE_VIEWER',
            ])
            ->allowMultipleChoices()
            ->setColumns(6)
            ->setHelp('Przeglądający: widzi tylko listę klientów, bez edycji i dodawania.')
            ->renderAsBadges([
                'ROLE_ADMIN' => 'danger',
                'ROLE_USER' => 'info',
                'ROLE_VIEWER' => 'secondary',
            ]);

        yield TextField::new('fullName', 'Użytkownik')
            ->onlyOnIndex();

        yield DateTimeField::new('createdAt', 'Utworzono')
            ->hideOnForm()
            ->setFormat('dd.MM.yyyy HH:mm');
    }

    public function configureActions(Actions $actions): Actions
    {
        $reset2fa = Action::new('reset2fa', 'Wyczyść 2FA', 'fa fa-shield-halved')
            ->linkToCrudAction('resetTwoFactor')
            ->displayIf(static function (User $user): bool {
                return $user->isGoogleAuthenticatorEnabled();
            });

        return $actions
            ->add(Crud::PAGE_INDEX, $reset2fa)
            ->add(Crud::PAGE_DETAIL, $reset2fa)
            ->reorder(Crud::PAGE_INDEX, ['reset2fa', Action::DETAIL, Action::EDIT, Action::DELETE]);
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $this->hashPassword($entityInstance);
        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $this->hashPassword($entityInstance);
        parent::updateEntity($entityManager, $entityInstance);
    }

    public function resetTwoFactor(AdminContext $context, EntityManagerInterface $entityManager, AdminUrlGenerator $urlGenerator): RedirectResponse
    {

        $user = $context->getEntity()->getInstance();

        $user->setGoogleAuthenticatorEnabled(false);
        $user->setGoogleAuthenticatorSecret(null);
        $entityManager->flush();

        $this->addFlash('success', sprintf('2FA zostało wyłączone dla użytkownika %s.', $user->getEmail()));

        $url = $urlGenerator
            ->setController(self::class)
            ->setAction(Action::INDEX)
            ->generateUrl();

        return $this->redirect($url);
    }

    private function hashPassword(User $user): void
    {
        $plainPassword = $this->getContext()?->getRequest()?->get('User')['password']['first'] ?? null;

        if (!empty($plainPassword)) {
            $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
            $user->setPassword($hashedPassword);
            $user->setMustChangePassword(true);
        }
    }
}

