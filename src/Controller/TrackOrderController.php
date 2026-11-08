<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class TrackOrderController extends AbstractController
{
    #[Route('/my-orders', name: 'track_orders', methods: ['GET'])]
    public function index(Request $request): Response
    {
        // Check if the customer is logged in.
        if (!$this->getUser()) {
            return $this->redirectToRoute('login');
        }

        $session = $request->getSession();

        // Get the ID of the currently logged-in customer.
        $userId = $this->getUser()->getId();

        // Retrieve all temporarily saved orders.
        $allOrders = $session->get('orders', []);

        // Only retrieve orders belonging to this customer.
        // Ignore older orders that do not have a user_id.
        $orders = array_filter(
            $allOrders,
            function ($order) use ($userId) {
                return isset($order['user_id'])
                    && (int) $order['user_id'] === (int) $userId;
            }
        );

        // Sort newest orders first.
        usort($orders, function ($a, $b) {
            return strtotime($b['created_at'])
                <=> strtotime($a['created_at']);
        });

        // Prepare order progress information.
        $steps = [
            'Pending',
            'Processing',
            'Shipped',
            'Completed',
        ];

        foreach ($orders as &$order) {
            $currentStep = array_search(
                $order['status'],
                $steps,
                true
            );

            $order['current_step'] = $currentStep === false
                ? -1
                : $currentStep;

            // Calculate item subtotals.
            foreach ($order['items'] as &$item) {
                $item['subtotal'] =
                    $item['price'] * $item['quantity'];
            }

            unset($item);
        }

        unset($order);

        return $this->render('home/orders.html.twig', [
            'orders' => $orders,
            'steps' => $steps,
        ]);
    }
}