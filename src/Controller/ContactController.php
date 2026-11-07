<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ContactController extends AbstractController
{
    #[Route('/contact', name: 'contact')]
    public function index(Request $request): Response
    {
        $success = false;
        $error = null;

        if ($request->isMethod('POST')) {
            $subject = trim($request->request->get('subject', ''));
            $message = trim($request->request->get('message', ''));

            if ($subject === '' || $message === '') {
                $error = 'Please fill in the subject and message.';
            } else {
                $success = true;
            }
        }

        return $this->render('home/contact.html.twig', [
            'success' => $success,
            'error' => $error,
        ]);
    }
}