<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SizeGuideController extends AbstractController
{
    #[Route('/size-guide', name: 'size_guide')]
    public function index(): Response
    {
        return $this->render('home/size_guide.html.twig');
    }
}