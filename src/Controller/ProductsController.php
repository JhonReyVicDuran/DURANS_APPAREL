<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ProductsController extends AbstractController
{
    #[Route('/admin/products', name: 'admin_products')]
    public function index(): Response
    {
        return $this->render('products/index.html.twig');
    }
}