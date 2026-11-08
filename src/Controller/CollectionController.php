<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use App\Repository\CategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CollectionController extends AbstractController
{
    #[Route('/collections', name: 'collections', methods: ['GET'])]
    public function index(
        Request $request,
        ProductRepository $productRepository,
        CategoryRepository $categoryRepository
    ): Response {

        // Retrieve categories from the database.
        $categories = $categoryRepository->findBy(
            [],
            ['categoryname' => 'ASC']
        );

        // Get the category selected by the customer.
        $selectedCategoryId = $request->query->get('category', '');

        $selectedCategory = null;

        if (
            $selectedCategoryId !== '' &&
            filter_var($selectedCategoryId, FILTER_VALIDATE_INT) !== false
        ) {
            $selectedCategory = $categoryRepository->find(
                (int) $selectedCategoryId
            );
        }

        // Filter products when a valid category is selected.
        if ($selectedCategory !== null) {

            $products = $productRepository->findBy(
                ['category' => $selectedCategory],
                ['id' => 'DESC']
            );

        } else {

            $products = $productRepository->findBy(
                [],
                ['id' => 'DESC']
            );

        }

        // Send all required variables to the Twig template.
        return $this->render('home/collection.html.twig', [
            'products' => $products,
            'categories' => $categories,
            'selectedCategoryId' => $selectedCategoryId,
        ]);
    }
}