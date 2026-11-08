<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CartController extends AbstractController
{
    private function isLoggedIn(): bool
    {
        return $this->getUser() !== null;
    }

    // Convert the database image value into its public browser path.
    private function getProductImagePath(?string $image): ?string
    {
        if ($image === null || trim($image) === '') {
            return null;
        }

        $image = ltrim(str_replace('\\', '/', trim($image)), '/');

        if (str_starts_with($image, 'admin/images/')) {
            return $image;
        }

        if (str_starts_with($image, 'products/')) {
            return 'admin/images/' . $image;
        }

        return 'admin/images/products/' . $image;
    }

    #[Route('/cart', name: 'cart', methods: ['GET'])]
    public function index(
        Request $request,
        ProductRepository $productRepository
    ): Response {
        if (!$this->isLoggedIn()) {
            return $this->redirectToRoute('login');
        }

        $session = $request->getSession();
        $cart = $session->get('cart', []);

        $refreshedCart = [];
        $subtotal = 0;
        $itemCount = 0;

        foreach ($cart as $itemKey => $item) {
            $product = $productRepository->find((int) $item['id']);

            if (!$product) {
                continue;
            }

            $quantity = (int) $item['quantity'];
            $size = (string) ($item['size'] ?? '');
            $availableSizes = $product->getSizes() ?? [];

            if (
                $quantity < 1 ||
                !in_array($size, $availableSizes, true)
            ) {
                continue;
            }

            $item['item_key'] = $itemKey;
            $item['id'] = $product->getId();
            $item['name'] = $product->getProductName();
            $item['category'] = $product->getCategory()->getCategoryname();
            $item['price'] = $product->getPrice();
            $item['image'] = $this->getProductImagePath($product->getImage());
            $item['stock'] = $product->getStock();
            $item['quantity'] = $quantity;
            $item['item_total'] = (float) $product->getPrice() * $quantity;

            $refreshedCart[$itemKey] = $item;

            $subtotal += $item['item_total'];
            $itemCount += $quantity;
        }

        $session->set('cart', $refreshedCart);

        return $this->render('home/cart.html.twig', [
            'cartItems' => $refreshedCart,
            'subtotal' => $subtotal,
            'itemCount' => $itemCount,
            'total' => $subtotal,
        ]);
    }

    #[Route('/cart/add/{id}', name: 'cart_add', methods: ['POST'])]
    public function add(
        Request $request,
        int $id,
        ProductRepository $productRepository
    ): JsonResponse {
        if (!$this->isLoggedIn()) {
            return $this->json([
                'success' => false,
                'login_required' => true,
                'message' => 'Please log in first.',
            ], 401);
        }

        $product = $productRepository->find($id);

        if (!$product) {
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

        $availableSizes = $product->getSizes() ?? [];
        $stock = $product->getStock();

        if ($stock < 1) {
            return $this->json([
                'success' => false,
                'message' => 'This product is out of stock.',
            ], 400);
        }

        if (!in_array($size, $availableSizes, true)) {
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

        $session = $request->getSession();
        $cart = $session->get('cart', []);

        $itemKey = $id . '_' . $size;
        $existingProductQuantity = 0;

        foreach ($cart as $item) {
            if ((int) $item['id'] === $id) {
                $existingProductQuantity += (int) $item['quantity'];
            }
        }

        if ($existingProductQuantity + $quantity > $stock) {
            return $this->json([
                'success' => false,
                'message' => 'You cannot add more than the available stock.',
            ], 400);
        }

        $newQuantity = ($cart[$itemKey]['quantity'] ?? 0) + $quantity;

        $cart[$itemKey] = [
            'item_key' => $itemKey,
            'id' => $product->getId(),
            'name' => $product->getProductName(),
            'category' => $product->getCategory()->getCategoryname(),
            'price' => $product->getPrice(),
            'image' => $this->getProductImagePath($product->getImage()),
            'stock' => $stock,
            'size' => $size,
            'quantity' => $newQuantity,
        ];

        $session->set('cart', $cart);

        $cartCount = array_sum(array_column($cart, 'quantity'));

        return $this->json([
            'success' => true,
            'message' => 'Product successfully added to your cart!',
            'cartCount' => $cartCount,
        ]);
    }

    #[Route('/cart/update', name: 'cart_update', methods: ['POST'])]
    public function update(
        Request $request,
        ProductRepository $productRepository
    ): JsonResponse {
        if (!$this->isLoggedIn()) {
            return $this->json([
                'success' => false,
                'login_required' => true,
                'message' => 'Please log in first.',
            ], 401);
        }

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

        $productId = (int) $cart[$itemKey]['id'];
        $product = $productRepository->find($productId);

        if (!$product) {
            return $this->json([
                'success' => false,
                'message' => 'This product is no longer available.',
            ], 404);
        }

        $availableSizes = $product->getSizes() ?? [];

        if (!in_array($cart[$itemKey]['size'], $availableSizes, true)) {
            return $this->json([
                'success' => false,
                'message' => 'The selected size is no longer available.',
            ], 400);
        }

        $otherQuantity = 0;

        foreach ($cart as $key => $item) {
            if (
                $key !== $itemKey &&
                (int) $item['id'] === $productId
            ) {
                $otherQuantity += (int) $item['quantity'];
            }
        }

        if ($otherQuantity + $quantity > $product->getStock()) {
            return $this->json([
                'success' => false,
                'message' => 'The selected quantity exceeds available stock.',
            ], 400);
        }

        $cart[$itemKey]['quantity'] = $quantity;
        $cart[$itemKey]['name'] = $product->getProductName();
        $cart[$itemKey]['category'] = $product->getCategory()->getCategoryname();
        $cart[$itemKey]['price'] = $product->getPrice();
        $cart[$itemKey]['image'] = $this->getProductImagePath($product->getImage());
        $cart[$itemKey]['stock'] = $product->getStock();

        $session->set('cart', $cart);

        return $this->json([
            'success' => true,
            'message' => 'Cart updated successfully.',
        ]);
    }

    #[Route('/cart/remove', name: 'cart_remove', methods: ['POST'])]
    public function remove(Request $request): JsonResponse
    {
        if (!$this->isLoggedIn()) {
            return $this->json([
                'success' => false,
                'login_required' => true,
                'message' => 'Please log in first.',
            ], 401);
        }

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
        if (!$this->isLoggedIn()) {
            return $this->json([
                'success' => false,
                'login_required' => true,
                'message' => 'Please log in first.',
            ], 401);
        }

        $request->getSession()->set('cart', []);

        return $this->json([
            'success' => true,
            'message' => 'Your cart has been cleared.',
        ]);
    }
}
