<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Constraints as Assert;

final class ChangePasswordController extends AbstractController
{
    #[Route('/change-password', name: 'app_change_password')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function __invoke(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
    ): Response {

        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        $constraints = [
            new Assert\NotBlank(message: 'Hasło jest wymagane.'),
            new Assert\Length(
                min: 8,
                minMessage: 'Hasło musi mieć co najmniej {{ limit }} znaków.'
            ),
            new Assert\Regex(
                pattern: '/(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^\w\s]).+/',
                message: 'Hasło musi zawierać małe i wielkie litery, cyfrę oraz znak specjalny.'
            ),
        ];

        $form = $this->createFormBuilder()
            ->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                'first_options' => [
                    'label' => 'Nowe hasło',
                    'attr' => ['autocomplete' => 'new-password'],
                ],
                'second_options' => [
                    'label' => 'Powtórz nowe hasło',
                    'attr' => ['autocomplete' => 'new-password'],
                ],
                'invalid_message' => 'Hasła muszą być identyczne.',
                'constraints' => $constraints,
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $data = $form->getData();
            $plainPassword = $data['password'];

            $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
            $user->setPassword($hashedPassword);
            $user->setMustChangePassword(false);

            $entityManager->flush();

            $this->addFlash('success', 'Hasło zostało zmienione. Możesz kontynuować pracę w systemie.');

            return $this->redirectToRoute('admin');
        }

        return $this->render('security/change_password.html.twig', [
            'changePasswordForm' => $form->createView(),
            'mustChangePassword' => $user->mustChangePassword(),
        ]);
    }
}

