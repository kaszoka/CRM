<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Google\GoogleAuthenticatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class TwoFactorSettingsController extends AbstractController
{
    #[Route('/admin/2fa', name: 'app_2fa_settings')]
    public function __invoke(
        Request $request,
        GoogleAuthenticatorInterface $googleAuthenticator,
        EntityManagerInterface $entityManager,
    ): Response {

        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        if ($request->isMethod('POST')) {
            if ($request->request->has('enable_2fa')) {
                if ($user->getGoogleAuthenticatorSecret() === null) {
                    $user->setGoogleAuthenticatorSecret($googleAuthenticator->generateSecret());
                }
                $user->setGoogleAuthenticatorEnabled(true);
                $entityManager->flush();

                $this->addFlash('success', 'Uwierzytelnianie dwuskładnikowe zostało włączone.');
            } elseif ($request->request->has('disable_2fa')) {
                $user->setGoogleAuthenticatorEnabled(false);
                $user->setGoogleAuthenticatorSecret(null);
                $entityManager->flush();

                $this->addFlash('success', 'Uwierzytelnianie dwuskładnikowe zostało wyłączone.');
            }

            return $this->redirectToRoute('app_2fa_settings');
        }

        $qrCodeUri = null;
        if ($user->isGoogleAuthenticatorEnabled()) {
            $qrCodeUri = $googleAuthenticator->getQRContent($user);
        }

        return $this->render('security/2fa_settings.html.twig', [
            'user' => $user,
            'isEnabled' => $user->isGoogleAuthenticatorEnabled(),
            'qrCodeUri' => $qrCodeUri,
        ]);
    }
}

