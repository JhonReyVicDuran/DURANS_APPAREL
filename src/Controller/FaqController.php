<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class FaqController extends AbstractController
{
    #[Route('/faqs', name: 'faqs')]
    public function index(): Response
    {
        return $this->render('home/faqs.html.twig');
    }
}