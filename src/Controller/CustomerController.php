<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\User;
use App\Form\CustomerEditFormType;
use App\Repository\UserRepository;
use App\Service\ActivityLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class CustomerController extends AbstractController
{
    // ==========================================
    // CUSTOMER LIST AND SEARCH
    // ==========================================

    #[Route(
        '/admin/customers',
        name: 'admin_customer_index',
        methods: ['GET']
    )]
    public function index(
        Request $request,
        UserRepository $userRepository
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $search = trim($request->query->get('q', ''));

        $customers = $userRepository->findCustomers($search);

        return $this->render('admin/customers/index.html.twig', [
            'customers' => $customers,
            'search' => $search,
        ]);
    }

    // ==========================================
    // EDIT CUSTOMER ACCOUNT
    // ==========================================

    #[Route(
        '/admin/customers/{id}/edit',
        name: 'admin_customer_edit',
        methods: ['GET', 'POST'],
        requirements: ['id' => '\d+']
    )]
    public function edit(
        int $id,
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        ActivityLogger $activityLogger,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $customer = $userRepository->find($id);

        if (
            !$customer instanceof User
            || !$this->isCustomer($customer)
        ) {
            throw $this->createNotFoundException(
                'Customer account not found.'
            );
        }

        $oldValues = [
            'fullName' => $customer->getFullName(),
            'email' => $customer->getEmail(),
        ];

        $form = $this->createForm(
            CustomerEditFormType::class,
            $customer
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $existingUser = $userRepository->findOneBy([
                'email' => $customer->getEmail(),
            ]);

            if (
                $existingUser
                && $existingUser->getId() !== $customer->getId()
            ) {
                $form->get('email')->addError(
                    new FormError(
                        'This email address is already registered.'
                    )
                );
            } else {

                // Get the optional new password from the form.
                $plainPassword = $form
                    ->get('plainPassword')
                    ->getData();

                $passwordChanged = false;

                // Only change the password if a new one was entered.
                if (
                    is_string($plainPassword)
                    && $plainPassword !== ''
                ) {
                    $hashedPassword = $passwordHasher->hashPassword(
                        $customer,
                        $plainPassword
                    );

                    $customer->setPassword($hashedPassword);

                    $passwordChanged = true;
                }

                // Save the updated customer information.
                $entityManager->flush();

                // Record the changes without exposing the password.
                $newValues = [
                    'fullName' => $customer->getFullName(),
                    'email' => $customer->getEmail(),
                    'passwordChanged' => $passwordChanged,
                ];

                $activityLogger->log(
                    'UPDATE',
                    'Customer Accounts',
                    'Updated customer account: '
                        . $customer->getFullName(),
                    $customer->getId(),
                    $oldValues,
                    $newValues
                );

                $this->addFlash(
                    'success',
                    $passwordChanged
                        ? 'Customer account and password updated successfully.'
                        : 'Customer account updated successfully.'
                );

                return $this->redirectToRoute(
                    'admin_customer_index'
                );
            }
        }

        return $this->render('admin/customers/edit.html.twig', [
            'form' => $form->createView(),
            'customer' => $customer,
        ]);
    }

    // ==========================================
    // ACTIVATE / DEACTIVATE CUSTOMER ACCOUNT
    // ==========================================

    #[Route(
        '/admin/customers/{id}/toggle-status',
        name: 'admin_customer_toggle_status',
        methods: ['POST'],
        requirements: ['id' => '\d+']
    )]
    public function toggleStatus(
        int $id,
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        ActivityLogger $activityLogger
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $customer = $userRepository->find($id);

        if (
            !$customer instanceof User
            || !$this->isCustomer($customer)
        ) {
            throw $this->createNotFoundException(
                'Customer account not found.'
            );
        }

        if (!$this->isCsrfTokenValid(
            'toggle_customer_' . $customer->getId(),
            $request->request->get('_token')
        )) {
            throw $this->createAccessDeniedException(
                'Invalid security token.'
            );
        }

        $oldStatus = $customer->isActive();
        $newStatus = !$oldStatus;

        $customer->setIsActive($newStatus);

        $entityManager->flush();

        $statusText = $newStatus ? 'activated' : 'deactivated';

        $activityLogger->log(
            'UPDATE',
            'Customer Accounts',
            'Customer account ' . $customer->getFullName()
                . ' was ' . $statusText . '.',
            $customer->getId(),
            [
                'isActive' => $oldStatus,
            ],
            [
                'isActive' => $newStatus,
            ]
        );

        $this->addFlash(
            'success',
            'Customer account successfully ' . $statusText . '.'
        );

        return $this->redirectToRoute('admin_customer_index');
    }

    // ==========================================
    // DELETE CUSTOMER ACCOUNT
    // ==========================================

    #[Route(
        '/admin/customers/{id}/delete',
        name: 'admin_customer_delete',
        methods: ['POST'],
        requirements: ['id' => '\d+']
    )]
    public function delete(
        int $id,
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        ActivityLogger $activityLogger
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $customer = $userRepository->find($id);

        if (
            !$customer instanceof User
            || !$this->isCustomer($customer)
        ) {
            throw $this->createNotFoundException(
                'Customer account not found.'
            );
        }

        if (!$this->isCsrfTokenValid(
            'delete_customer_' . $customer->getId(),
            $request->request->get('_token')
        )) {
            throw $this->createAccessDeniedException(
                'Invalid security token.'
            );
        }

        // Check whether the customer has existing orders.
        $orderCount = $entityManager
            ->getRepository(Order::class)
            ->count([
                'user' => $customer,
            ]);

        if ($orderCount > 0) {
            $this->addFlash(
                'error',
                'This customer cannot be deleted because they have '
                    . $orderCount
                    . ' existing order(s). Deactivate the account instead.'
            );

            return $this->redirectToRoute('admin_customer_index');
        }

        // Save customer details for the Activity Log before deletion.
        $customerId = $customer->getId();
        $customerName = $customer->getFullName();

        $activityLogger->log(
            'DELETE',
            'Customer Accounts',
            'Deleted customer account: ' . $customerName,
            $customerId,
            [
                'fullName' => $customerName,
                'email' => $customer->getEmail(),
            ],
            null
        );

        $entityManager->remove($customer);
        $entityManager->flush();

        $this->addFlash(
            'success',
            'Customer account deleted successfully.'
        );

        return $this->redirectToRoute('admin_customer_index');
    }

    // ==========================================
    // CUSTOMER ROLE CHECK
    // ==========================================

    private function isCustomer(User $user): bool
    {
        return !in_array('ROLE_ADMIN', $user->getRoles(), true)
            && !in_array('ROLE_STAFF', $user->getRoles(), true);
    }
}