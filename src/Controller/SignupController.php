<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SignupController extends AbstractController
{
    #[Route('/signup', name: 'signup', methods: ['GET', 'POST'])]
    public function index(Request $request): Response
    {
        $error = null;
        $success = false;

        $fullname = '';
        $email = '';

        if ($request->isMethod('POST')) {
            $fullname = trim($request->request->get('fullname', ''));
            $email = trim($request->request->get('email', ''));
            $password = $request->request->get('password', '');
            $confirmPassword = $request->request->get('confirmPassword', '');
            $terms = $request->request->get('terms');

            if (
                $fullname === '' ||
                $email === '' ||
                $password === '' ||
                $confirmPassword === ''
            ) {
                $error = 'Please fill in all required fields.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Please enter a valid email address.';
            } elseif (strlen($password) < 6) {
                $error = 'Password must be at least 6 characters.';
            } elseif ($password !== $confirmPassword) {
                $error = 'Passwords do not match.';
            } elseif ($terms !== 'on') {
                $error = 'Please agree to the Terms & Conditions and Privacy Policy.';
            } else {
                $success = true;

                // Frontend demonstration only.
                // Account creation will be connected to the database later.

                $fullname = '';
                $email = '';
            }
        }

        return $this->render('home/signup.html.twig', [
            'error' => $error,
            'success' => $success,
            'fullname' => $fullname,
            'email' => $email,
        ]);
    }
}