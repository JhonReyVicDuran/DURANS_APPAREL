<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractController
{
    #[Route('/admin/dashboard', name: 'admin_dashboard')]
    public function index(): Response
    {
        return $this->render('dashboard/index.html.twig', [
            'total_products' => 0,
            'low_stock' => 0,
            'total_orders' => 0,
            'total_sales' => 0,
        ]);
    }
}