<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class OrdersController extends AbstractController
{
    #[Route('/admin/orders', name: 'admin_orders', methods: ['GET'])]
    public function index(): Response
    {
        // Hardcoded orders
        $orders = [
            [
                'order_id' => 4,
                'customer_name' => 'Jhon Rey Vic A. Duran',
                'email' => 'jhonreyvicduran@gmail.com',
                'phone' => '09345479747',
                'address' => 'Purok 1 East Balabag, Valencia, Negros Oriental',
                'total_amount' => 5131.00,
                'status' => 'Completed',
                'order_date' => '2026-09-10 10:05:07',
            ],
            [
                'order_id' => 3,
                'customer_name' => 'Jhon Rey Vic A. Duran',
                'email' => 'jhonreyvicduran@gmail.com',
                'phone' => '09552982462',
                'address' => 'Purok 1 East Balabag, Valencia, Negros Oriental',
                'total_amount' => 498.00,
                'status' => 'Processing',
                'order_date' => '2026-09-09 14:15:43',
            ],
            [
                'order_id' => 2,
                'customer_name' => 'Jhon Rey Vic A. Duran',
                'email' => 'jhonreyvicduran@gmail.com',
                'phone' => '09552982462',
                'address' => 'Purok 1 East Balabag, Valencia, Negros Oriental',
                'total_amount' => 858.00,
                'status' => 'Shipped',
                'order_date' => '2026-09-09 14:15:10',
            ],
            [
                'order_id' => 1,
                'customer_name' => 'Jhon Rey Vic A. Duran',
                'email' => 'jhonreyvicduran@gmail.com',
                'phone' => '09552982462',
                'address' => 'Purok 1 East Balabag, Valencia, Negros Oriental',
                'total_amount' => 999.00,
                'status' => 'Cancelled',
                'order_date' => '2026-09-09 13:32:41',
            ],
        ];

        return $this->render('orders/index.html.twig', [
            'orders' => $orders,
        ]);
    }
}
