<?php

namespace App\Controller;

use App\Entity\Product;
use App\Entity\Category;
use App\Repository\ProductRepository;
use App\Repository\CategoryRepository;
use App\Service\ActivityLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ProductsController extends AbstractController
{
    private const AVAILABLE_SIZES = [
        'XS',
        'S',
        'M',
        'L',
        'XL',
        'XXL',
        'ONE SIZE',
    ];

    private const DEFAULT_CATEGORIES = [
        'T-Shirt',
        'Hoodie',
        'Shorts',
        'Compression Wear',
        'Accessories',
    ];

    private function denyUnauthorizedProductAccess(): void
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

    private function ensureDefaultCategories(
        CategoryRepository $categoryRepository,
        EntityManagerInterface $entityManager
    ): array {
        foreach (self::DEFAULT_CATEGORIES as $categoryName) {
            $existingCategory = $categoryRepository->findOneBy([
                'categoryname' => $categoryName,
            ]);

            if (!$existingCategory) {
                $category = new Category();
                $category->setCategoryname($categoryName);
                $category->setDescription('Products under ' . $categoryName);
                $entityManager->persist($category);
            }
        }

        $entityManager->flush();

        return $categoryRepository->findBy([], ['categoryname' => 'ASC']);
    }

    private function validateSizes(array $sizesInput): array
    {
        return array_values(array_unique(array_filter(
            $sizesInput,
            fn ($size) => is_string($size) && in_array($size, self::AVAILABLE_SIZES, true)
        )));
    }

    private function validateImage(?UploadedFile $image): ?string
    {
        if (!$image) {
            return 'Please select a product image.';
        }

        if (!$image->isValid()) {
            return 'The image upload failed. Please try again.';
        }

        if ($image->getSize() > 5 * 1024 * 1024) {
            return 'The image must not be larger than 5 MB.';
        }

        $allowedMimeTypes = [
            'image/jpeg',
            'image/png',
            'image/webp',
        ];

        if (!in_array($image->getMimeType(), $allowedMimeTypes, true)) {
            return 'Please upload a JPG, PNG, or WEBP image.';
        }

        return null;
    }

    private function saveProductImage(UploadedFile $image): string
    {
        $extension = $image->guessExtension() ?: 'jpg';

        $fileName = bin2hex(random_bytes(16)) . '.' . $extension;

        $directory = $this->getParameter('kernel.project_dir')
            . '/public/admin/images/products';

        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new \RuntimeException('Unable to create the product image folder.');
        }

        $image->move($directory, $fileName);

        return 'products/' . $fileName;
    }

    private function removeProductImage(?string $imagePath): void
    {
        if (!$imagePath) {
            return;
        }

        $directory = $this->getParameter('kernel.project_dir')
            . '/public/admin/images/';

        $fullPath = $directory . $imagePath;

        if (is_file($fullPath)) {
            unlink($fullPath);
        }
    }

    #[Route('/admin/products', name: 'admin_products', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        EntityManagerInterface $entityManager,
        ProductRepository $productRepository,
        CategoryRepository $categoryRepository,
        ActivityLogger $activityLogger
    ): Response {
        $this->denyUnauthorizedProductAccess();

        $error = null;

        $categories = $this->ensureDefaultCategories(
            $categoryRepository,
            $entityManager
        );

        if ($request->isMethod('POST')) {
            $productName = trim((string) $request->request->get('product_name', ''));
            $categoryId = $request->request->get('category_id');
            $price = trim((string) $request->request->get('price', ''));
            $stock = $request->request->get('stock');
            $sizesInput = $request->request->all('sizes');
            $description = trim((string) $request->request->get('description', ''));
            $image = $request->files->get('image');

            $category = null;

            if ($categoryId !== null && filter_var($categoryId, FILTER_VALIDATE_INT) !== false) {
                $category = $categoryRepository->find((int) $categoryId);
            }

            if (!$this->isCsrfTokenValid('add_product', $request->request->get('_csrf_token'))) {
                $error = 'Invalid form submission. Please try again.';
            } elseif ($productName === '' || !$category || $price === '' || $stock === null || $description === '') {
                $error = 'Please fill in all required fields.';
            } elseif (!is_numeric($price) || (float) $price < 0) {
                $error = 'Please enter a valid product price.';
            } elseif (filter_var($stock, FILTER_VALIDATE_INT) === false || (int) $stock < 0) {
                $error = 'Please enter a valid stock quantity.';
            } elseif (!is_array($sizesInput) || count($sizesInput) === 0) {
                $error = 'Please select at least one available size.';
            } elseif ($image !== null && !$image instanceof UploadedFile) {
                $error = 'Invalid image upload.';
            } else {
                $sizes = $this->validateSizes($sizesInput);
                $imageError = $this->validateImage($image);

                if (count($sizes) === 0) {
                    $error = 'Please select valid product sizes.';
                } elseif ($imageError !== null) {
                    $error = $imageError;
                } else {
                    try {
                        $imagePath = $this->saveProductImage($image);

                        $product = new Product();
                        $product->setProductName($productName);
                        $product->setCategory($category);
                        $product->setPrice(number_format((float) $price, 2, '.', ''));
                        $product->setStock((int) $stock);
                        $product->setSizes($sizes);
                        $product->setDescription($description);
                        $product->setImage($imagePath);

                        $entityManager->persist($product);
                        $entityManager->flush();

                        $activityLogger->log(
                            'CREATE',
                            'Products',
                            'Added a new product: ' . $productName,
                            $product->getId(),
                            null,
                            [
                                'product_name' => $productName,
                                'price' => $product->getPrice(),
                                'stock' => $product->getStock(),
                                'sizes' => $sizes,
                                'description' => $description,
                            ]
                        );

                        $this->addFlash('success', 'Product added successfully!');

                        return $this->redirectToRoute('admin_products');
                    } catch (\Throwable $exception) {
                        $error = 'Unable to save the product image or product. Please try again.';
                    }
                }
            }
        }

        $products = $productRepository->findBy([], ['id' => 'DESC']);

        return $this->render('products/index.html.twig', [
            'products' => $products,
            'categories' => $categories,
            'availableSizes' => self::AVAILABLE_SIZES,
            'error' => $error,
        ]);
    }

    #[Route('/admin/products/{id}/edit', name: 'admin_product_edit', methods: ['GET', 'POST'])]
    public function edit(
        int $id,
        Request $request,
        ProductRepository $productRepository,
        CategoryRepository $categoryRepository,
        EntityManagerInterface $entityManager,
        ActivityLogger $activityLogger
    ): Response {
        $this->denyUnauthorizedProductAccess();

        $product = $productRepository->find($id);

        if (!$product) {
            throw $this->createNotFoundException('Product not found.');
        }

        $categories = $this->ensureDefaultCategories(
            $categoryRepository,
            $entityManager
        );

        $error = null;

        if ($request->isMethod('POST')) {
            $productName = trim((string) $request->request->get('product_name', ''));
            $categoryId = $request->request->get('category_id');
            $price = trim((string) $request->request->get('price', ''));
            $stock = $request->request->get('stock');
            $sizesInput = $request->request->all('sizes');
            $description = trim((string) $request->request->get('description', ''));
            $image = $request->files->get('image');

            $category = null;

            if ($categoryId !== null && filter_var($categoryId, FILTER_VALIDATE_INT) !== false) {
                $category = $categoryRepository->find((int) $categoryId);
            }

            if (!$this->isCsrfTokenValid('edit_product_' . $id, $request->request->get('_csrf_token'))) {
                $error = 'Invalid form submission. Please try again.';
            } elseif ($productName === '' || !$category || $price === '' || $stock === null || $description === '') {
                $error = 'Please fill in all required fields.';
            } elseif (!is_numeric($price) || (float) $price < 0) {
                $error = 'Please enter a valid product price.';
            } elseif (filter_var($stock, FILTER_VALIDATE_INT) === false || (int) $stock < 0) {
                $error = 'Please enter a valid stock quantity.';
            } elseif (!is_array($sizesInput) || count($sizesInput) === 0) {
                $error = 'Please select at least one available size.';
            } elseif ($image !== null && !$image instanceof UploadedFile) {
                $error = 'Invalid image upload.';
            } else {
                $sizes = $this->validateSizes($sizesInput);
                $imageError = $image ? $this->validateImage($image) : null;

                if (count($sizes) === 0) {
                    $error = 'Please select valid product sizes.';
                } elseif ($imageError !== null) {
                    $error = $imageError;
                } else {
                    try {
                        $oldValues = [
                            'product_name' => $product->getProductName(),
                            'price' => $product->getPrice(),
                            'stock' => $product->getStock(),
                            'sizes' => $product->getSizes(),
                            'description' => $product->getDescription(),
                        ];

                        $oldImage = $product->getImage();

                        if ($image instanceof UploadedFile) {
                            $product->setImage($this->saveProductImage($image));
                        }

                        $product->setProductName($productName);
                        $product->setCategory($category);
                        $product->setPrice(number_format((float) $price, 2, '.', ''));
                        $product->setStock((int) $stock);
                        $product->setSizes($sizes);
                        $product->setDescription($description);

                        $entityManager->flush();

                        $newValues = [
                            'product_name' => $product->getProductName(),
                            'price' => $product->getPrice(),
                            'stock' => $product->getStock(),
                            'sizes' => $product->getSizes(),
                            'description' => $product->getDescription(),
                        ];

                        $activityLogger->log(
                            'UPDATE',
                            'Products',
                            'Updated product: ' . $product->getProductName(),
                            $product->getId(),
                            $oldValues,
                            $newValues
                        );

                        if ($image instanceof UploadedFile && $oldImage !== $product->getImage()) {
                            $this->removeProductImage($oldImage);
                        }

                        $this->addFlash('success', 'Product updated successfully!');

                        return $this->redirectToRoute('admin_products');
                    } catch (\Throwable $exception) {
                        $error = 'Unable to update the product. Please try again.';
                    }
                }
            }
        }

        return $this->render('products/edit.html.twig', [
            'product' => $product,
            'categories' => $categories,
            'availableSizes' => self::AVAILABLE_SIZES,
            'error' => $error,
        ]);
    }

    #[Route('/admin/products/{id}/delete', name: 'admin_product_delete', methods: ['POST'])]
    public function delete(
        int $id,
        Request $request,
        ProductRepository $productRepository,
        EntityManagerInterface $entityManager,
        ActivityLogger $activityLogger
    ): Response {
        $this->denyUnauthorizedProductAccess();

        $product = $productRepository->find($id);

        if (!$product) {
            throw $this->createNotFoundException('Product not found.');
        }

        if (!$this->isCsrfTokenValid('delete_product_' . $id, $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid delete request. Please try again.');

            return $this->redirectToRoute('admin_products');
        }

        $imagePath = $product->getImage();
        $productName = $product->getProductName();
        $productId = $product->getId();

        try {
            $entityManager->remove($product);
            $entityManager->flush();

            $activityLogger->log(
                'DELETE',
                'Products',
                'Deleted product: ' . $productName,
                $productId,
                [
                    'product_name' => $productName,
                ],
                null
            );

            $this->removeProductImage($imagePath);

            $this->addFlash('success', 'Product deleted successfully!');
        } catch (\Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException $exception) {
            $this->addFlash('error', 'This product cannot be deleted because it is already included in an order.');
        } catch (\Throwable $exception) {
            $this->addFlash('error', 'Unable to delete the product. Please try again.');
        }

        return $this->redirectToRoute('admin_products');
    }
}