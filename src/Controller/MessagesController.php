<?php

namespace App\Controller;

use App\Entity\ContactMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class MessagesController extends AbstractController
{
    #[Route('/admin/messages', name: 'admin_messages', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

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
        EntityManagerInterface $entityManager
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $message = $entityManager
            ->getRepository(ContactMessage::class)
            ->find($id);

        if (!$message) {
            throw $this->createNotFoundException('Message not found.');
        }

        if ($message->getStatus() === 'Unread') {
            $message->setStatus('Read');
            $entityManager->flush();
        }

        return $this->render('admin/messages/show.html.twig', [
            'message' => $message,
        ]);
    }
}