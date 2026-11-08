<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class SignupController extends AbstractController
{
    #[Route('/signup', name: 'signup', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        UserRepository $userRepository
    ): Response {

        // Redirect users who are already logged in.
        if ($this->getUser()) {
            if ($this->isGranted('ROLE_ADMIN')) {
                return $this->redirectToRoute('admin_dashboard');
            }

            return $this->redirectToRoute('app_home');
        }

        $error = null;

        $fullname = '';
        $email = '';

        if ($request->isMethod('POST')) {

            $fullname = trim((string) $request->request->get('fullname', ''));
            $email = strtolower(trim((string) $request->request->get('email', '')));

            $password = (string) $request->request->get('password', '');
            $confirmPassword = (string) $request->request->get('confirmPassword', '');
            $terms = $request->request->get('terms');

            // Check CSRF token.
            if (!$this->isCsrfTokenValid(
                'signup',
                $request->request->get('_csrf_token')
            )) {
                $error = 'Invalid form submission. Please try again.';
            } elseif (
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
            } elseif ($userRepository->findOneBy(['email' => $email])) {
                $error = 'An account with this email address already exists.';
            } else {

                // Create a new customer account.
                $user = new User();

                $user->setFullName($fullname);
                $user->setEmail($email);

                // Public signup can only create regular customers.
                $user->setRoles(['ROLE_USER']);

                // Hash the password before saving it.
                $hashedPassword = $passwordHasher->hashPassword(
                    $user,
                    $password
                );

                $user->setPassword($hashedPassword);

                // Save the account to MySQL.
                $entityManager->persist($user);
                $entityManager->flush();

                // Show a one-time success message on the login page.
                $this->addFlash(
                    'success',
                    'Your account has been created successfully! You can now log in.'
                );

                return $this->redirectToRoute('login');
            }
        }

        return $this->render('home/signup.html.twig', [
            'error' => $error,
            'success' => false,
            'fullname' => $fullname,
            'email' => $email,
        ]);
    }
}