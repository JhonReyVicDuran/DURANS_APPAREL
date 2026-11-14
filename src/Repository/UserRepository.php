<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Find customer accounts, optionally filtered by name or email.
     *
     * Admin and Staff accounts are excluded.
     */
    public function findCustomers(?string $search = null): array
    {
        $queryBuilder = $this->createQueryBuilder('u')
            ->orderBy('u.createdAt', 'DESC');

        // Search by customer name or email when a search term is provided.
        if ($search !== null && trim($search) !== '') {
            $searchTerm = '%' . strtolower(trim($search)) . '%';

            $queryBuilder
                ->andWhere(
                    'LOWER(u.fullName) LIKE :search
                    OR LOWER(u.email) LIKE :search'
                )
                ->setParameter('search', $searchTerm);
        }

        $users = $queryBuilder->getQuery()->getResult();

        // Exclude Admin and Staff accounts.
        return array_values(array_filter(
            $users,
            function (User $user): bool {
                return !in_array('ROLE_ADMIN', $user->getRoles(), true)
                    && !in_array('ROLE_STAFF', $user->getRoles(), true);
            }
        ));
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(
        PasswordAuthenticatedUserInterface $user,
        string $newHashedPassword
    ): void {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(
                sprintf('Instances of "%s" are not supported.', $user::class)
            );
        }

        $user->setPassword($newHashedPassword);

        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }
}