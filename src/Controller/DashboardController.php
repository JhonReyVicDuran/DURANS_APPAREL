<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use App\Repository\OrderRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractController
{
    #[Route('/staff/dashboard', name: 'staff_dashboard')]
    public function index(
        ProductRepository $productRepository,
        OrderRepository $orderRepository
    ): Response {
        // Only Staff accounts can access this dashboard.
        $this->denyAccessUnlessGranted('ROLE_STAFF');

        // Count all products.
        $totalProducts = $productRepository->count([]);

        // Count products with 5 or fewer items remaining.
        $lowStock = $productRepository->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.stock <= :threshold')
            ->setParameter('threshold', 5)
            ->getQuery()
            ->getSingleScalarResult();

        // Count all customer orders.
        $totalOrders = $orderRepository->count([]);

        // Calculate completed sales only.
        $totalSales = $orderRepository->createQueryBuilder('o')
            ->select('COALESCE(SUM(o.totalAmount), 0)')
            ->where('o.status = :status')
            ->setParameter('status', 'Completed')
            ->getQuery()
            ->getSingleScalarResult();

        // Get the five most recent orders.
        $recentOrders = $orderRepository->findBy(
            [],
            ['createdAt' => 'DESC'],
            5
        );

        // Get up to five products with the lowest stock.
        $lowStockProducts = $productRepository->createQueryBuilder('p')
            ->where('p.stock <= :threshold')
            ->setParameter('threshold', 5)
            ->orderBy('p.stock', 'ASC')
            ->addOrderBy('p.productName', 'ASC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        return $this->render('dashboard/index.html.twig', [
            'total_products' => $totalProducts,
            'low_stock' => (int) $lowStock,
            'total_orders' => $totalOrders,
            'total_sales' => (float) $totalSales,
            'recent_orders' => $recentOrders,
            'low_stock_products' => $lowStockProducts,
        ]);
    }
}