<?php

namespace App\Controller;

use App\Entity\Order;
use App\Repository\OrderRepository;
use App\Service\ActivityLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class OrdersController extends AbstractController
{
    private function denyUnauthorizedOrderAccess(): void
    {
        if (
            !$this->isGranted('ROLE_ADMIN')
            && !$this->isGranted('ROLE_STAFF')
        ) {
            throw $this->createAccessDeniedException(
                'You do not have permission to access this page.'
            );
        }
    }

    #[Route('/admin/orders', name: 'admin_orders', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        OrderRepository $orderRepository,
        EntityManagerInterface $entityManager,
        ActivityLogger $activityLogger
    ): Response {
        $this->denyUnauthorizedOrderAccess();

        // Process an Admin's or Staff member's order status update.
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
                // Save the previous status before changing it.
                $oldStatus = $order->getStatus();

                $order->setStatus($newStatus);

                $entityManager->flush();

                // Record activity only when the status actually changes.
                if ($oldStatus !== $newStatus) {
                    $activityLogger->log(
                        'UPDATE',
                        'Orders',
                        'Updated order #' . $order->getOrderNumber()
                            . ' status from ' . $oldStatus
                            . ' to ' . $newStatus . '.',
                        $order->getId(),
                        [
                            'status' => $oldStatus,
                        ],
                        [
                            'status' => $newStatus,
                        ]
                    );
                }

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