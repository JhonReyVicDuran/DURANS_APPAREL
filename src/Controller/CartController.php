<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CartController extends AbstractController
{
    // Temporary product catalog.
    // Keep these details consistent with CollectionController.
    private function getProducts(): array
    {
        return [
            1 => [
                'id' => 1,
                'name' => 'Classic Black Shirt',
                'category' => 'T-Shirt',
                'price' => 499.00,
                'image' => null,
                'stock' => 15,
                'sizes' => ['S', 'M', 'L', 'XL'],
            ],
            2 => [
                'id' => 2,
                'name' => 'Essential White Shirt',
                'category' => 'T-Shirt',
                'price' => 450.00,
                'image' => null,
                'stock' => 12,
                'sizes' => ['XS', 'S', 'M', 'L', 'XL'],
            ],
            3 => [
                'id' => 3,
                'name' => 'Premium Black Hoodie',
                'category' => 'Hoodie',
                'price' => 899.00,
                'image' => null,
                'stock' => 8,
                'sizes' => ['M', 'L', 'XL', 'XXL'],
            ],
            4 => [
                'id' => 4,
                'name' => 'Classic Training Shorts',
                'category' => 'Shorts',
                'price' => 599.00,
                'image' => null,
                'stock' => 0,
                'sizes' => ['S', 'M', 'L'],
            ],
            5 => [
                'id' => 5,
                'name' => 'Compression Shirt',
                'category' => 'Compression Wear',
                'price' => 699.00,
                'image' => null,
                'stock' => 10,
                'sizes' => ['S', 'M', 'L', 'XL'],
            ],
            6 => [
                'id' => 6,
                'name' => 'Essential Cap',
                'category' => 'Accessories',
                'price' => 299.00,
                'image' => null,
                'stock' => 20,
                'sizes' => ['ONE SIZE'],
            ],
        ];
    }

    #[Route('/cart', name: 'cart', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $cart = $request->getSession()->get('cart', []);

        $subtotal = 0;
        $itemCount = 0;

        foreach ($cart as &$item) {
            $item['item_total'] = $item['price'] * $item['quantity'];

            $subtotal += $item['item_total'];
            $itemCount += $item['quantity'];
        }
        unset($item);

        return $this->render('home/cart.html.twig', [
            'cartItems' => $cart,
            'subtotal' => $subtotal,
            'itemCount' => $itemCount,
            'total' => $subtotal,
        ]);
    }

    #[Route('/cart/add/{id}', name: 'cart_add', methods: ['POST'])]
    public function add(Request $request, int $id): JsonResponse
    {
        $products = $this->getProducts();

        if (!isset($products[$id])) {
            return $this->json([
                'success' => false,
                'message' => 'Product not found.',
            ], 404);
        }

        $data = $request->toArray();

        $size = trim((string) ($data['size'] ?? ''));
        $quantity = filter_var(
            $data['quantity'] ?? 1,
            FILTER_VALIDATE_INT
        );

        $product = $products[$id];

        if ($product['stock'] < 1) {
            return $this->json([
                'success' => false,
                'message' => 'This product is out of stock.',
            ], 400);
        }

        if (!in_array($size, $product['sizes'], true)) {
            return $this->json([
                'success' => false,
                'message' => 'Please select a valid size.',
            ], 400);
        }

        if ($quantity === false || $quantity < 1) {
            return $this->json([
                'success' => false,
                'message' => 'Please select a valid quantity.',
            ], 400);
        }

        if ($quantity > $product['stock']) {
            return $this->json([
                'success' => false,
                'message' => 'The selected quantity exceeds available stock.',
            ], 400);
        }

        $session = $request->getSession();
        $cart = $session->get('cart', []);

        // Same product with different sizes gets a separate cart entry.
        $itemKey = $id . '_' . $size;

        $existingQuantity = $cart[$itemKey]['quantity'] ?? 0;
        $newQuantity = $existingQuantity + $quantity;

        if ($newQuantity > $product['stock']) {
            return $this->json([
                'success' => false,
                'message' => 'You cannot add more than the available stock.',
            ], 400);
        }

        $cart[$itemKey] = [
            'item_key' => $itemKey,
            'id' => $product['id'],
            'name' => $product['name'],
            'category' => $product['category'],
            'price' => $product['price'],
            'image' => $product['image'],
            'stock' => $product['stock'],
            'size' => $size,
            'quantity' => $newQuantity,
        ];

        $session->set('cart', $cart);

        $cartCount = array_sum(
            array_column($cart, 'quantity')
        );

        return $this->json([
            'success' => true,
            'message' => 'Product successfully added to your cart!',
            'cartCount' => $cartCount,
        ]);
    }

    #[Route('/cart/update', name: 'cart_update', methods: ['POST'])]
    public function update(Request $request): JsonResponse
    {
        $data = $request->toArray();

        $itemKey = (string) ($data['itemKey'] ?? '');
        $quantity = filter_var(
            $data['quantity'] ?? null,
            FILTER_VALIDATE_INT
        );

        $session = $request->getSession();
        $cart = $session->get('cart', []);

        if (!isset($cart[$itemKey])) {
            return $this->json([
                'success' => false,
                'message' => 'Cart item not found.',
            ], 404);
        }

        if ($quantity === false || $quantity < 1) {
            return $this->json([
                'success' => false,
                'message' => 'Quantity must be at least 1.',
            ], 400);
        }

        $item = $cart[$itemKey];

        if ($quantity > $item['stock']) {
            return $this->json([
                'success' => false,
                'message' => 'The selected quantity exceeds available stock.',
            ], 400);
        }

        $cart[$itemKey]['quantity'] = $quantity;

        $session->set('cart', $cart);

        return $this->json([
            'success' => true,
            'message' => 'Cart updated successfully.',
        ]);
    }

    #[Route('/cart/remove', name: 'cart_remove', methods: ['POST'])]
    public function remove(Request $request): JsonResponse
    {
        $data = $request->toArray();
        $itemKey = (string) ($data['itemKey'] ?? '');

        $session = $request->getSession();
        $cart = $session->get('cart', []);

        if (!isset($cart[$itemKey])) {
            return $this->json([
                'success' => false,
                'message' => 'Cart item not found.',
            ], 404);
        }

        unset($cart[$itemKey]);

        $session->set('cart', $cart);

        return $this->json([
            'success' => true,
            'message' => 'Product removed from cart.',
        ]);
    }

    #[Route('/cart/clear', name: 'cart_clear', methods: ['POST'])]
    public function clear(Request $request): JsonResponse
    {
        $request->getSession()->set('cart', []);

        return $this->json([
            'success' => true,
            'message' => 'Your cart has been cleared.',
        ]);
    }
}