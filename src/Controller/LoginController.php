<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class LoginController extends AbstractController
{
    #[Route('/login', name: 'login', methods: ['GET', 'POST'])]
    public function index(
        AuthenticationUtils $authenticationUtils
    ): Response {

        // Redirect users who are already logged in.
        if ($this->getUser()) {

            // ADMIN
            if ($this->isGranted('ROLE_ADMIN')) {
                return $this->redirectToRoute('admin_dashboard');
            }

            // STAFF
            if ($this->isGranted('ROLE_STAFF')) {
                return $this->redirectToRoute('staff_dashboard');
            }

            // CUSTOMER
            return $this->redirectToRoute('app_home');
        }

        // Get the last login error.
        $error = $authenticationUtils->getLastAuthenticationError();

        // Get the previously entered email.
        $lastEmail = $authenticationUtils->getLastUsername();

        return $this->render('home/login.html.twig', [
            'error' => $error ? $error->getMessage() : '',
            'email' => $lastEmail,
        ]);
    }
}