<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\Product;
use App\Entity\User;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CheckoutController extends AbstractController
{
    #[Route('/checkout', name: 'checkout', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        EntityManagerInterface $entityManager,
        ProductRepository $productRepository
    ): Response {
        // Make sure the customer is logged in.
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->redirectToRoute('login');
        }

        $session = $request->getSession();
        $cart = $session->get('cart', []);

        // Prevent checkout with an empty cart.
        if (empty($cart)) {
            return $this->redirectToRoute('cart');
        }

        // Default customer information.
        $customerName = $user->getFullName();
        $email = $user->getEmail();
        $phone = '';
        $address = '';
        $error = '';

        // Get the latest product information from MySQL.
        $cartItems = [];
        $totalCents = 0;

        foreach ($cart as $itemKey => $item) {
            $product = $productRepository->find((int) $item['id']);

            if (!$product) {
                $error = 'A product in your cart is no longer available.';
                continue;
            }

            $quantity = (int) $item['quantity'];
            $priceCents = (int) round((float) $product->getPrice() * 100);
            $itemTotalCents = $priceCents * $quantity;

            $totalCents += $itemTotalCents;

            $cartItems[] = [
                'item_key' => $itemKey,
                'id' => $product->getId(),
                'name' => $product->getProductName(),
                'category' => $product->getCategory()->getCategoryname(),
                'price' => $product->getPrice(),
                'image' => $product->getImage(),
                'stock' => $product->getStock(),
                'size' => $item['size'],
                'quantity' => $quantity,
                'item_total' => $itemTotalCents / 100,
            ];
        }

        $total = $totalCents / 100;

        // Process the checkout form.
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

            // Validate the submitted form.
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
            }

            if ($error === '') {
                $connection = $entityManager->getConnection();

                try {
                    // Begin a database transaction.
                    $connection->beginTransaction();

                    $products = [];
                    $productQuantities = [];
                    $orderItemsData = [];
                    $finalTotalCents = 0;

                    // Validate every item using current database records.
                    foreach ($cart as $item) {
                        $productId = (int) $item['id'];
                        $quantity = (int) $item['quantity'];
                        $size = trim((string) $item['size']);

                        $product = $entityManager->find(
                            Product::class,
                            $productId
                        );

                        if (!$product) {
                            throw new \RuntimeException(
                                'A product in your cart is no longer available.'
                            );
                        }

                        if ($quantity < 1) {
                            throw new \RuntimeException(
                                'Invalid product quantity.'
                            );
                        }

                        $availableSizes = $product->getSizes() ?? [];

                        if (!in_array($size, $availableSizes, true)) {
                            throw new \RuntimeException(
                                'The selected size for '
                                . $product->getProductName()
                                . ' is no longer available.'
                            );
                        }

                        $products[$productId] = $product;

                        // Combine quantities for the same product.
                        $productQuantities[$productId] =
                            ($productQuantities[$productId] ?? 0)
                            + $quantity;

                        // Calculate using cents to avoid common decimal errors.
                        $priceCents = (int) round(
                            (float) $product->getPrice() * 100
                        );

                        $finalTotalCents += $priceCents * $quantity;

                        $orderItemsData[] = [
                            'product' => $product,
                            'size' => $size,
                            'quantity' => $quantity,
                            'price' => $product->getPrice(),
                        ];
                    }

                    if (empty($orderItemsData)) {
                        throw new \RuntimeException(
                            'Your cart is empty.'
                        );
                    }

                    // Check the combined quantity against available stock.
                    foreach ($productQuantities as $productId => $quantity) {
                        $product = $products[$productId];

                        if ($quantity > $product->getStock()) {
                            throw new \RuntimeException(
                                'Not enough stock for '
                                . $product->getProductName()
                                . '. Available stock: '
                                . $product->getStock()
                            );
                        }
                    }

                    // Create the main order.
                    $order = new Order();

                    $order->setOrderNumber(
                        strtoupper(bin2hex(random_bytes(4)))
                    );

                    $order->setUser($user);
                    $order->setCustomerName($customerName);
                    $order->setEmail($email);
                    $order->setPhone($phone);
                    $order->setAddress($address);
                    $order->setPaymentMethod('Cash on Delivery');
                    $order->setTotalAmount(
                        $this->formatCents($finalTotalCents)
                    );
                    $order->setStatus('Pending');

                    // Create an order item for every cart entry.
                    foreach ($orderItemsData as $itemData) {
                        $product = $itemData['product'];

                        $orderItem = new OrderItem();

                        $orderItem->setProduct($product);
                        $orderItem->setProductName(
                            $product->getProductName()
                        );
                        $orderItem->setProductImage(
                            $product->getImage()
                        );
                        $orderItem->setSize($itemData['size']);
                        $orderItem->setQuantity($itemData['quantity']);
                        $orderItem->setUnitPrice($itemData['price']);

                        $order->addItem($orderItem);
                    }

                    // Deduct the purchased quantities from product stock.
                    foreach ($productQuantities as $productId => $quantity) {
                        $product = $products[$productId];

                        $product->setStock(
                            $product->getStock() - $quantity
                        );
                    }

                    // Save the order, its items, and updated stock.
                    $entityManager->persist($order);
                    $entityManager->flush();

                    // Commit the transaction after successful saving.
                    $connection->commit();

                    // Clear the cart only after successful checkout.
                    $session->set('cart', []);

                    return $this->redirectToRoute(
                        'checkout_success',
                        [
                            'orderNumber' => $order->getOrderNumber(),
                        ]
                    );
                } catch (\Throwable $exception) {
                    // Undo database changes if checkout fails.
                    if ($connection->isTransactionActive()) {
                        $connection->rollBack();
                    }

                    if ($exception instanceof \RuntimeException) {
                        $error = $exception->getMessage();
                    } else {
                        $error = 'We could not place your order. Please try again.';
                    }
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

    // Display the saved order confirmation.
    #[Route(
        '/checkout/success/{orderNumber}',
        name: 'checkout_success',
        methods: ['GET']
    )]
    public function success(
        string $orderNumber,
        OrderRepository $orderRepository
    ): Response {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->redirectToRoute('login');
        }

        // Retrieve the order from MySQL.
        $order = $orderRepository->findOneBy([
            'orderNumber' => $orderNumber,
        ]);

        if (!$order) {
            return $this->redirectToRoute('cart');
        }

        // Prevent customers from viewing another customer's order.
        if ($order->getUser()->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('home/checkout_success.html.twig', [
            'order' => [
                'order_number' => $order->getOrderNumber(),
                'total_amount' => $order->getTotalAmount(),
                'payment_method' => $order->getPaymentMethod(),
                'address' => $order->getAddress(),
                'status' => $order->getStatus(),
            ],
        ]);
    }

    // Convert cents into a decimal amount.
    private function formatCents(int $cents): string
    {
        return intdiv($cents, 100)
            . '.'
            . str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }
}