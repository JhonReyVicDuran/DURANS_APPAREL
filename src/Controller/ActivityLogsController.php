<?php

namespace App\Controller;

use App\Entity\ActivityLog;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ActivityLogsController extends AbstractController
{
    #[Route('/admin/activity-logs', name: 'admin_activity_logs', methods: ['GET'])]
    public function index(
        EntityManagerInterface $entityManager
    ): Response {
        // Only Admin accounts can access the activity logs.
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Retrieve the latest 100 recorded activities.
        $activities = $entityManager
            ->getRepository(ActivityLog::class)
            ->findBy(
                [],
                ['createdAt' => 'DESC'],
                100
            );

        return $this->render('admin/activity_logs/index.html.twig', [
            'activities' => $activities,
        ]);
    }
}