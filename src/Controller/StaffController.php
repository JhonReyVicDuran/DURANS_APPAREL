<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\StaffEditFormType;
use App\Form\StaffRegistrationFormType;
use App\Repository\UserRepository;
use App\Service\ActivityLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class StaffController extends AbstractController
{
    // ==========================================
    // STAFF LIST
    // ==========================================

    #[Route('/admin/staff', name: 'admin_staff_index', methods: ['GET'])]
    public function index(UserRepository $userRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $users = $userRepository->findAll();

        $staffMembers = array_values(array_filter(
            $users,
            function (User $user): bool {
                return in_array('ROLE_STAFF', $user->getRoles(), true);
            }
        ));

        usort(
            $staffMembers,
            function (User $a, User $b): int {
                return $b->getCreatedAt() <=> $a->getCreatedAt();
            }
        );

        return $this->render('admin/staff/index.html.twig', [
            'staffMembers' => $staffMembers,
        ]);
    }

    // ==========================================
    // CREATE STAFF ACCOUNT
    // ==========================================

    #[Route('/admin/staff/new', name: 'admin_staff_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        UserPasswordHasherInterface $passwordHasher,
        ActivityLogger $activityLogger
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $staff = new User();

        $form = $this->createForm(
            StaffRegistrationFormType::class,
            $staff
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $existingUser = $userRepository->findOneBy([
                'email' => $staff->getEmail(),
            ]);

            if ($existingUser) {
                $form->get('email')->addError(
                    new FormError('This email address is already registered.')
                );
            } else {
                $plainPassword = $form->get('plainPassword')->getData();

                $hashedPassword = $passwordHasher->hashPassword(
                    $staff,
                    $plainPassword
                );

                $staff->setPassword($hashedPassword);
                $staff->setRoles(['ROLE_STAFF']);
                $staff->setIsActive(true);

                $entityManager->persist($staff);
                $entityManager->flush();

                $activityLogger->log(
                    'CREATE',
                    'Staff Accounts',
                    'Created Staff account: ' . $staff->getFullName(),
                    $staff->getId(),
                    null,
                    [
                        'fullName' => $staff->getFullName(),
                        'email' => $staff->getEmail(),
                        'role' => 'ROLE_STAFF',
                    ]
                );

                $this->addFlash(
                    'success',
                    'Staff account created successfully.'
                );

                return $this->redirectToRoute('admin_staff_new');
            }
        }

        return $this->render('admin/staff/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    // ==========================================
    // EDIT STAFF ACCOUNT
    // ==========================================

    #[Route(
        '/admin/staff/{id}/edit',
        name: 'admin_staff_edit',
        methods: ['GET', 'POST'],
        requirements: ['id' => '\d+']
    )]
    public function edit(
        int $id,
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        ActivityLogger $activityLogger
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $staff = $userRepository->find($id);

        if (!$staff) {
            throw $this->createNotFoundException('Staff account not found.');
        }

        if (!in_array('ROLE_STAFF', $staff->getRoles(), true)) {
            throw $this->createNotFoundException('Staff account not found.');
        }

        $oldValues = [
            'fullName' => $staff->getFullName(),
            'email' => $staff->getEmail(),
        ];

        $form = $this->createForm(
            StaffEditFormType::class,
            $staff
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $existingUser = $userRepository->findOneBy([
                'email' => $staff->getEmail(),
            ]);

            if ($existingUser && $existingUser->getId() !== $staff->getId()) {
                $form->get('email')->addError(
                    new FormError('This email address is already registered.')
                );
            } else {
                $plainPassword = $form->get('plainPassword')->getData();

                if ($plainPassword !== null && $plainPassword !== '') {

                    if (strlen($plainPassword) < 8) {
                        $form->get('plainPassword')->addError(
                            new FormError(
                                'The new password must be at least 8 characters long.'
                            )
                        );
                    } else {
                        $hashedPassword = $passwordHasher->hashPassword(
                            $staff,
                            $plainPassword
                        );

                        $staff->setPassword($hashedPassword);
                    }
                }

                if ($form->getErrors(true)->count() === 0) {

                    $entityManager->flush();

                    $activityLogger->log(
                        'UPDATE',
                        'Staff Accounts',
                        'Updated Staff account: ' . $staff->getFullName(),
                        $staff->getId(),
                        $oldValues,
                        [
                            'fullName' => $staff->getFullName(),
                            'email' => $staff->getEmail(),
                        ]
                    );

                    $this->addFlash(
                        'success',
                        'Staff account updated successfully.'
                    );

                    return $this->redirectToRoute('admin_staff_index');
                }
            }
        }

        return $this->render('admin/staff/edit.html.twig', [
            'form' => $form->createView(),
            'staff' => $staff,
        ]);
    }

    // ==========================================
    // ACTIVATE / DEACTIVATE STAFF ACCOUNT
    // ==========================================

    #[Route(
        '/admin/staff/{id}/toggle-status',
        name: 'admin_staff_toggle_status',
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

        $staff = $userRepository->find($id);

        if (!$staff) {
            throw $this->createNotFoundException('Staff account not found.');
        }

        if (!in_array('ROLE_STAFF', $staff->getRoles(), true)) {
            throw $this->createNotFoundException('Staff account not found.');
        }

        // Validate the CSRF token before changing the account.
        if (!$this->isCsrfTokenValid(
            'toggle_staff_' . $staff->getId(),
            $request->request->get('_token')
        )) {
            throw $this->createAccessDeniedException(
                'Invalid security token.'
            );
        }

        $oldStatus = $staff->isActive();

        // Reverse the current account status.
        $newStatus = !$oldStatus;

        $staff->setIsActive($newStatus);

        $entityManager->flush();

        $statusText = $newStatus ? 'activated' : 'deactivated';

        // Record the status change in the Activity Log.
        $activityLogger->log(
            'UPDATE',
            'Staff Accounts',
            'Staff account ' . $staff->getFullName() . ' was ' . $statusText . '.',
            $staff->getId(),
            [
                'isActive' => $oldStatus,
            ],
            [
                'isActive' => $newStatus,
            ]
        );

        $this->addFlash(
            'success',
            'Staff account successfully ' . $statusText . '.'
        );

        return $this->redirectToRoute('admin_staff_index');
    }
}