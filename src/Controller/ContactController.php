<?php

namespace App\Controller;

use App\Entity\ContactMessage;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ContactController extends AbstractController
{
    #[Route('/contact', name: 'contact', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $error = null;

        $formValues = [
            'fullName' => '',
            'email' => '',
            'subject' => '',
            'message' => '',
        ];

        $user = $this->getUser();

        if ($user instanceof User) {
            $formValues['fullName'] = $user->getFullName() ?? '';
            $formValues['email'] = $user->getEmail() ?? '';
        }

        if ($request->isMethod('POST')) {
            $formValues['subject'] = trim($request->request->get('subject', ''));
            $formValues['message'] = trim($request->request->get('message', ''));

            if (!$user instanceof User) {
                $formValues['fullName'] = trim($request->request->get('fullName', ''));
                $formValues['email'] = strtolower(trim($request->request->get('email', '')));
            }

            if (!$this->isCsrfTokenValid('contact_form', $request->request->get('_token'))) {
                $error = 'Your form session has expired. Please refresh the page and try again.';
            } elseif (
                $formValues['fullName'] === '' ||
                $formValues['email'] === '' ||
                $formValues['subject'] === '' ||
                $formValues['message'] === ''
            ) {
                $error = 'Please complete all fields before sending your message.';
            } elseif (!filter_var($formValues['email'], FILTER_VALIDATE_EMAIL)) {
                $error = 'Please enter a valid email address.';
            } elseif (strlen($formValues['fullName']) > 150) {
                $error = 'Your name must not exceed 150 characters.';
            } elseif (strlen($formValues['email']) > 180) {
                $error = 'Your email address is too long.';
            } elseif (strlen($formValues['subject']) > 255) {
                $error = 'Your subject must not exceed 255 characters.';
            } else {
                $contactMessage = new ContactMessage();

                $contactMessage
                    ->setFullName($formValues['fullName'])
                    ->setEmail($formValues['email'])
                    ->setSubject($formValues['subject'])
                    ->setMessage($formValues['message'])
                    ->setStatus('Unread');

                $entityManager->persist($contactMessage);
                $entityManager->flush();

                $this->addFlash(
                    'contact_success',
                    'Your message has been sent successfully! We appreciate you contacting DURAN\'S Apparel.'
                );

                return $this->redirectToRoute('contact');
            }
        }

        return $this->render('home/contact.html.twig', [
            'error' => $error,
            'formValues' => $formValues,
        ]);
    }
}