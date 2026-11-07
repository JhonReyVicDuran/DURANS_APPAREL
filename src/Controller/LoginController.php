<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class LoginController extends AbstractController
{
    #[Route('/login', name: 'login', methods: ['GET', 'POST'])]
    public function index(Request $request): Response
    {
        $error = '';
        $email = '';

        if ($request->isMethod('POST')) {
            $email = trim($request->request->get('email', ''));
            $password = $request->request->get('password', '');

            if ($email === '' || $password === '') {
                $error = 'Please enter your email/username and password.';
            } else {

                // TEMPORARY LOGIN SESSION
                $session = $request->getSession();

                $temporaryUserId = 'TEMP-' . substr(
                    hash('sha256', strtolower($email)),
                    0,
                    12
                );

                $session->set('user_id', $temporaryUserId);
                $session->set('user_email', $email);

                // Redirect after successful temporary login
                return $this->redirectToRoute('collections');
            }
        }

        return $this->render('home/login.html.twig', [
            'error' => $error,
            'email' => $email,
        ]);
    }
}