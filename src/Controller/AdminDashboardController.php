<?php

namespace App\Controller;

use App\Entity\ActivityLog;
use App\Entity\User;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AdminDashboardController extends AbstractController
{
    #[Route('/admin/dashboard', name: 'admin_dashboard')]
    public function index(
        UserRepository $userRepository,
        ProductRepository $productRepository,
        OrderRepository $orderRepository,
        EntityManagerInterface $entityManager
    ): Response {
        // Only Admin accounts can access this dashboard.
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Initialize account statistics.
        $totalCustomers = 0;
        $totalStaff = 0;
        $activeStaff = 0;
        $inactiveStaff = 0;

        // Retrieve all registered accounts.
        $users = $userRepository->findAll();

        // Separate Customers, Staff, and Admin accounts.
        foreach ($users as $user) {
            $roles = $user->getRoles();

            if (in_array('ROLE_ADMIN', $roles, true)) {
                continue;
            }

            if (in_array('ROLE_STAFF', $roles, true)) {
                $totalStaff++;

                if ($user->isActive()) {
                    $activeStaff++;
                } else {
                    $inactiveStaff++;
                }

                continue;
            }

            // Accounts without Admin or Staff roles are Customers.
            $totalCustomers++;
        }

        // Count all products.
        $totalProducts = $productRepository->count([]);

        // Count all orders.
        $totalOrders = $orderRepository->count([]);

        // Calculate completed sales only.
        $totalSales = $orderRepository->createQueryBuilder('o')
            ->select('COALESCE(SUM(o.totalAmount), 0)')
            ->where('o.status = :status')
            ->setParameter('status', 'Completed')
            ->getQuery()
            ->getSingleScalarResult();

        // Retrieve the eight most recent activities.
        $recentActivities = $entityManager
            ->getRepository(ActivityLog::class)
            ->findBy(
                [],
                ['createdAt' => 'DESC'],
                8
            );

        return $this->render('admin/dashboard/index.html.twig', [
            'total_customers' => $totalCustomers,
            'total_staff' => $totalStaff,
            'active_staff' => $activeStaff,
            'inactive_staff' => $inactiveStaff,
            'total_products' => $totalProducts,
            'total_orders' => $totalOrders,
            'total_sales' => (float) $totalSales,
            'recent_activities' => $recentActivities,
        ]);
    }
}