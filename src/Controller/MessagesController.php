<?php

namespace App\Controller;

use App\Entity\ContactMessage;
use App\Service\ActivityLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class MessagesController extends AbstractController
{
    private function denyUnauthorizedMessageAccess(): void
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

    #[Route('/admin/messages', name: 'admin_messages', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $this->denyUnauthorizedMessageAccess();

        $repository = $entityManager->getRepository(ContactMessage::class);

        $messages = $repository->findBy([], ['createdAt' => 'DESC']);
        $unreadCount = $repository->count(['status' => 'Unread']);

        return $this->render('admin/messages/index.html.twig', [
            'messages' => $messages,
            'unreadCount' => $unreadCount,
        ]);
    }

    #[Route('/admin/messages/{id}', name: 'admin_message_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(
        int $id,
        EntityManagerInterface $entityManager,
        ActivityLogger $activityLogger
    ): Response {
        $this->denyUnauthorizedMessageAccess();

        $message = $entityManager
            ->getRepository(ContactMessage::class)
            ->find($id);

        if (!$message) {
            throw $this->createNotFoundException('Message not found.');
        }

        // Record the previous status before changing it.
        $oldStatus = $message->getStatus();

        if ($oldStatus === 'Unread') {
            $message->setStatus('Read');
            $entityManager->flush();

            // Record the successful status change.
            $activityLogger->log(
                'UPDATE',
                'Messages',
                'Marked customer message #' . $message->getId() . ' as Read.',
                $message->getId(),
                [
                    'status' => $oldStatus,
                ],
                [
                    'status' => 'Read',
                ]
            );
        }

        return $this->render('admin/messages/show.html.twig', [
            'message' => $message,
        ]);
    }
}