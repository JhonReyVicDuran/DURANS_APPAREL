<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CheckoutController extends AbstractController
{
    // Display checkout and process orders.
    #[Route('/checkout', name: 'checkout', methods: ['GET', 'POST'])]
    public function index(Request $request): Response
    {
        // Require the customer to be logged in.
        if (!$this->getUser()) {
            return $this->redirectToRoute('login');
        }

        $session = $request->getSession();

        $cart = $session->get('cart', []);

        // Prevent checkout with an empty cart.
        if (empty($cart)) {
            return $this->redirectToRoute('cart');
        }

        // Get customer information from the session.
        $email = $session->get('user_email', '');

        $customerName = '';
        $phone = '';
        $address = '';
        $error = '';

        // Calculate the order summary.
        $cartItems = [];
        $total = 0;

        foreach ($cart as $item) {
            $item['item_total'] =
                $item['price'] * $item['quantity'];

            $total += $item['item_total'];

            $cartItems[] = $item;
        }

        // Process Place Order.
        if ($request->isMethod('POST')) {

            $customerName = trim(
                $request->request->get('customer_name', '')
            );

            $email = trim(
                $request->request->get('email', '')
            );

            $phone = trim(
                $request->request->get('phone', '')
            );

            $address = trim(
                $request->request->get('address', '')
            );

            // Validate CSRF token.
            if (!$this->isCsrfTokenValid(
                'checkout_order',
                $request->request->get('_token')
            )) {
                $error = 'Invalid request. Please try again.';

            } elseif ($customerName === '') {
                $error = 'Please enter your full name.';

            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Please enter a valid email address.';

            } elseif (!preg_match('/^[0-9+\-\s]{7,20}$/', $phone)) {
                $error = 'Please enter a valid mobile number.';

            } elseif ($address === '') {
                $error = 'Please enter your delivery address.';

            } else {

                // Check the cart again before creating the order.
                $latestCart = [];
                $productQuantities = [];
                $finalTotal = 0;

                foreach ($cart as $item) {

                    $quantity = (int) $item['quantity'];
                    $stock = (int) $item['stock'];
                    $productId = (int) $item['id'];

                    if ($quantity < 1 || $stock < 0) {
                        $error = 'Invalid cart item.';
                        break;
                    }

                    // Add quantities of the same product together.
                    $productQuantities[$productId] =
                        ($productQuantities[$productId] ?? 0)
                        + $quantity;

                    $item['item_total'] =
                        $item['price'] * $quantity;

                    $finalTotal += $item['item_total'];

                    $latestCart[] = $item;
                }

                // Check total quantity against available stock.
                if ($error === '') {
                    foreach ($latestCart as $item) {
                        $productId = (int) $item['id'];

                        if (
                            $productQuantities[$productId]
                            > (int) $item['stock']
                        ) {
                            $error = 'Not enough stock for '
                                . $item['name'] . '.';
                            break;
                        }
                    }
                }

                if ($error === '' && empty($latestCart)) {
                    $error = 'Your cart is empty.';
                }

                // Create the order if validation succeeds.
                if ($error === '') {

                    $orderNumber = strtoupper(
                        bin2hex(random_bytes(4))
                    );

                    $order = [
                        'order_number' => $orderNumber,

                        // Use the currently logged-in Symfony user.
                        'user_id' => $this->getUser()->getId(),

                        'customer_name' => $customerName,
                        'email' => $email,
                        'phone' => $phone,
                        'address' => $address,
                        'payment_method' => 'Cash on Delivery',
                        'items' => $latestCart,
                        'total_amount' => $finalTotal,
                        'status' => 'Pending',
                        'created_at' => date('Y-m-d H:i:s'),
                    ];

                    // Save the order temporarily in the session.
                    $orders = $session->get('orders', []);

                    $orders[$orderNumber] = $order;

                    $session->set('orders', $orders);

                    // Clear the cart only after saving the order.
                    $session->set('cart', []);

                    return $this->redirectToRoute(
                        'checkout_success',
                        ['orderNumber' => $orderNumber]
                    );
                }
            }
        }

        return $this->render('home/checkout.html.twig', [
            'cartItems' => $cartItems,
            'total' => $total,
            'customerName' => $customerName,
            'email' => $email,
            'phone' => $phone,
            'address' => $address,
            'error' => $error,
        ]);
    }

    // Display successful order confirmation.
    #[Route(
        '/checkout/success/{orderNumber}',
        name: 'checkout_success',
        methods: ['GET']
    )]
    public function success(
        Request $request,
        string $orderNumber
    ): Response {

        // Require the customer to be logged in.
        if (!$this->getUser()) {
            return $this->redirectToRoute('login');
        }

        $session = $request->getSession();

        $orders = $session->get('orders', []);

        if (!isset($orders[$orderNumber])) {
            return $this->redirectToRoute('cart');
        }

        $order = $orders[$orderNumber];

        // Make sure customers can only view their own orders.
        if (
            !isset($order['user_id'])
            || (int) $order['user_id'] !== (int) $this->getUser()->getId()
        ) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('home/checkout_success.html.twig', [
            'order' => $order,
        ]);
    }
}