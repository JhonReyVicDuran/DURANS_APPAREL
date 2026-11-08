<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\OrderRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class TrackOrderController extends AbstractController
{
    #[Route('/my-orders', name: 'track_orders', methods: ['GET'])]
    public function index(OrderRepository $orderRepository): Response
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->redirectToRoute('login');
        }

        $steps = [
            'Pending',
            'Processing',
            'Shipped',
            'Completed',
        ];

        $ordersFromDatabase = $orderRepository->findBy(
            ['user' => $user],
            ['createdAt' => 'DESC']
        );

        $orders = [];

        foreach ($ordersFromDatabase as $order) {
            $items = [];

            foreach ($order->getItems() as $orderItem) {
                $quantity = $orderItem->getQuantity();
                $price = (float) $orderItem->getUnitPrice();

                // Use the saved image first.
                $image = $orderItem->getProductImage();

                // Fall back to the current product image if necessary.
                if (empty($image) && $orderItem->getProduct()) {
                    $image = $orderItem->getProduct()->getImage();
                }

                $items[] = [
                    'name' => $orderItem->getProductName(),
                    'image' => $image,
                    'quantity' => $quantity,
                    'size' => $orderItem->getSize(),
                    'price' => $price,
                    'subtotal' => $price * $quantity,
                ];
            }

            $status = $order->getStatus();
            $currentStep = array_search($status, $steps, true);

            if ($currentStep === false) {
                $currentStep = 0;
            } else {
                $currentStep++;
            }

            $orders[] = [
                'order_number' => $order->getOrderNumber(),
                'created_at' => $order->getCreatedAt(),
                'status' => $order->getStatus(),
                'current_step' => $currentStep,
                'items' => $items,
                'customer_name' => $order->getCustomerName(),
                'email' => $order->getEmail(),
                'phone' => $order->getPhone(),
                'address' => $order->getAddress(),
                'payment_method' => $order->getPaymentMethod(),
                'total_amount' => $order->getTotalAmount(),
            ];
        }

        return $this->render('home/orders.html.twig', [
            'orders' => $orders,
            'steps' => $steps,
        ]);
    }
}