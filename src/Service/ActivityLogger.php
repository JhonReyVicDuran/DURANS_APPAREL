<?php

namespace App\Service;

use App\Entity\ActivityLog;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class ActivityLogger
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private Security $security
    ) {
    }

    public function log(
        string $action,
        string $module,
        string $description,
        ?int $recordId = null,
        ?array $oldValues = null,
        ?array $newValues = null
    ): void {
        $user = $this->security->getUser();

        // Only authenticated User accounts can be recorded.
        if (!$user instanceof User) {
            return;
        }

        $activity = new ActivityLog();

        $activity
            ->setPerformedBy($user)
            ->setAction($action)
            ->setModule($module)
            ->setDescription($description)
            ->setRecordId($recordId)
            ->setOldValues($oldValues)
            ->setNewValues($newValues);

        $this->entityManager->persist($activity);
        $this->entityManager->flush();
    }
}