<?php

namespace App\Controller;

use App\Entity\Product;
use App\Entity\Category;
use App\Repository\ProductRepository;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ProductsController extends AbstractController
{
    #[Route('/admin/products', name: 'admin_products', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        EntityManagerInterface $entityManager,
        ProductRepository $productRepository,
        CategoryRepository $categoryRepository
    ): Response {

        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $error = null;

        // Create default categories if the category table is empty.
        $categories = $categoryRepository->findAll();

        if (count($categories) === 0) {
            $defaultCategories = [
                'T-Shirt',
                'Hoodie',
                'Shorts',
                'Compression Wear',
                'Accessories',
            ];

            foreach ($defaultCategories as $categoryName) {
                $category = new Category();
                $category->setCategoryname($categoryName);
                $category->setDescription(
                    'Products under ' . $categoryName
                );

                $entityManager->persist($category);
            }

            $entityManager->flush();

            $categories = $categoryRepository->findBy(
                [],
                ['categoryname' => 'ASC']
            );
        }

        // Available size choices.
        $availableSizes = [
            'XS',
            'S',
            'M',
            'L',
            'XL',
            'XXL',
            'ONE SIZE',
        ];

        if ($request->isMethod('POST')) {

            $productName = trim(
                (string) $request->request->get('product_name', '')
            );

            $categoryId = $request->request->get('category_id');
            $price = trim(
                (string) $request->request->get('price', '')
            );

            $stock = $request->request->get('stock');

            $sizesInput = $request->request->all('sizes');

            $description = trim(
                (string) $request->request->get('description', '')
            );

            $category = null;

            if (
                $categoryId !== null &&
                filter_var($categoryId, FILTER_VALIDATE_INT) !== false
            ) {
                $category = $categoryRepository->find((int) $categoryId);
            }

            // Validate the form.
            if (!$this->isCsrfTokenValid(
                'add_product',
                $request->request->get('_csrf_token')
            )) {
                $error = 'Invalid form submission. Please try again.';

            } elseif (
                $productName === '' ||
                !$category ||
                $price === '' ||
                $stock === null ||
                $description === ''
            ) {
                $error = 'Please fill in all required fields.';

            } elseif (!is_numeric($price) || (float) $price < 0) {
                $error = 'Please enter a valid product price.';

            } elseif (
                filter_var($stock, FILTER_VALIDATE_INT) === false ||
                (int) $stock < 0
            ) {
                $error = 'Please enter a valid stock quantity.';

            } elseif (!is_array($sizesInput) || count($sizesInput) === 0) {
                $error = 'Please select at least one available size.';

            } else {

                // Keep only valid sizes and remove duplicates.
                $sizes = array_values(array_unique(array_filter(
                    $sizesInput,
                    fn($size) => in_array($size, $availableSizes, true)
                )));

                if (count($sizes) === 0) {
                    $error = 'Please select valid product sizes.';

                } else {

                    $product = new Product();

                    $product->setProductName($productName);
                    $product->setCategory($category);
                    $product->setPrice(
                        number_format((float) $price, 2, '.', '')
                    );
                    $product->setStock((int) $stock);
                    $product->setSizes($sizes);
                    $product->setDescription($description);

                    $entityManager->persist($product);
                    $entityManager->flush();

                    $this->addFlash(
                        'success',
                        'Product added successfully!'
                    );

                    return $this->redirectToRoute('admin_products');
                }
            }
        }

        $products = $productRepository->findBy(
            [],
            ['id' => 'DESC']
        );

        return $this->render('products/index.html.twig', [
            'products' => $products,
            'categories' => $categories,
            'availableSizes' => $availableSizes,
            'error' => $error,
        ]);
    }
}
