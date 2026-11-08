<?php

namespace App\Controller;

use App\Entity\Order;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class OrdersController extends AbstractController
{
    #[Route('/admin/orders', name: 'admin_orders', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        OrderRepository $orderRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Process an admin's order status update.
        if ($request->isMethod('POST')) {
            $orderId = $request->request->getInt('order_id');
            $newStatus = $request->request->get('status');

            $allowedStatuses = [
                'Pending',
                'Processing',
                'Shipped',
                'Completed',
                'Cancelled',
            ];

            $order = $orderRepository->find($orderId);

            if (!$order instanceof Order) {
                $this->addFlash('error', 'Order not found.');
            } elseif (!$this->isCsrfTokenValid(
                'update_order_status_' . $orderId,
                $request->request->get('_token')
            )) {
                $this->addFlash('error', 'Invalid request. Please try again.');
            } elseif (!in_array($newStatus, $allowedStatuses, true)) {
                $this->addFlash('error', 'Invalid order status.');
            } else {
                $order->setStatus($newStatus);

                $entityManager->flush();

                $this->addFlash(
                    'success',
                    'Order #' . $order->getOrderNumber() . ' status updated successfully.'
                );
            }

            return $this->redirectToRoute('admin_orders');
        }

        // Retrieve actual customer orders from MySQL.
        $orders = $orderRepository->findBy(
            [],
            ['createdAt' => 'DESC']
        );

        return $this->render('orders/index.html.twig', [
            'orders' => $orders,
            'statuses' => [
                'Pending',
                'Processing',
                'Shipped',
                'Completed',
                'Cancelled',
            ],
        ]);
    }
}
