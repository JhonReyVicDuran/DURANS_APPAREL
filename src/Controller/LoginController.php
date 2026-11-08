<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class LoginController extends AbstractController
{
    #[Route('/login', name: 'login', methods: ['GET', 'POST'])]
    public function index(
        AuthenticationUtils $authenticationUtils
    ): Response {

        // If the user is already logged in, redirect them.
        if ($this->getUser()) {
            if ($this->isGranted('ROLE_ADMIN')) {
                return $this->redirectToRoute('admin_dashboard');
            }

            return $this->redirectToRoute('app_home');
        }

        // Get the last login error, if authentication failed.
        $error = $authenticationUtils->getLastAuthenticationError();

        // Get the email entered during the previous login attempt.
        $lastEmail = $authenticationUtils->getLastUsername();

        return $this->render('home/login.html.twig', [
            'error' => $error ? $error->getMessage() : '',
            'email' => $lastEmail,
        ]);
    }
}