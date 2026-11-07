<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CollectionController extends AbstractController
{
    #[Route('/collections', name: 'collections')]
    public function index(): Response
    {
        // Temporary sample products
        $products = [
            [
                'id' => 1,
                'name' => 'Classic Black Shirt',
                'category' => 'T-Shirt',
                'description' => 'A simple and comfortable everyday shirt.',
                'price' => 499.00,
                'image' => null,
                'stock' => 15,
                'sizes' => ['S', 'M', 'L', 'XL'],
            ],
            [
                'id' => 2,
                'name' => 'Essential White Shirt',
                'category' => 'T-Shirt',
                'description' => 'A clean and minimal design for everyday wear.',
                'price' => 450.00,
                'image' => null,
                'stock' => 12,
                'sizes' => ['XS', 'S', 'M', 'L', 'XL'],
            ],
            [
                'id' => 3,
                'name' => 'Premium Black Hoodie',
                'category' => 'Hoodie',
                'description' => 'A stylish hoodie designed for comfort.',
                'price' => 899.00,
                'image' => null,
                'stock' => 8,
                'sizes' => ['M', 'L', 'XL', 'XXL'],
            ],
            [
                'id' => 4,
                'name' => 'Classic Training Shorts',
                'category' => 'Shorts',
                'description' => 'Lightweight shorts for everyday activities.',
                'price' => 599.00,
                'image' => null,
                'stock' => 0,
                'sizes' => ['S', 'M', 'L'],
            ],
            [
                'id' => 5,
                'name' => 'Compression Shirt',
                'category' => 'Compression Wear',
                'description' => 'A comfortable fit for active lifestyles.',
                'price' => 699.00,
                'image' => null,
                'stock' => 10,
                'sizes' => ['S', 'M', 'L', 'XL'],
            ],
            [
                'id' => 6,
                'name' => 'Essential Cap',
                'category' => 'Accessories',
                'description' => 'A simple accessory to complete your outfit.',
                'price' => 299.00,
                'image' => null,
                'stock' => 20,
                'sizes' => ['ONE SIZE'],
            ],
        ];

        return $this->render('home/collection.html.twig', [
            'products' => $products,
        ]);
    }
}