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
                $error = 'Login is not available yet. Please try again once the account system is connected.';
            }
        }

        return $this->render('home/login.html.twig', [
            'error' => $error,
            'email' => $email,
        ]);
    }
}