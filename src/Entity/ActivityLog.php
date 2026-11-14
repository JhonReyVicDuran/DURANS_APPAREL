<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'activity_log')]
class ActivityLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * The account that performed the action.
     * This becomes NULL if that account is deleted.
     */
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $performedBy = null;

    /**
     * Keep the actor's identity even if their account is deleted.
     */
    #[ORM\Column(length: 255)]
    private ?string $actorName = null;

    #[ORM\Column(length: 180)]
    private ?string $actorEmail = null;

    /**
     * Examples: CREATE, UPDATE, DELETE, LOGIN.
     */
    #[ORM\Column(length: 30)]
    private string $action = '';

    /**
     * Examples: Product, Order, Message, User.
     */
    #[ORM\Column(length: 50)]
    private string $module = '';

    /**
     * ID of the affected record, if applicable.
     */
    #[ORM\Column(nullable: true)]
    private ?int $recordId = null;

    /**
     * Human-readable explanation of the activity.
     */
    #[ORM\Column(type: Types::TEXT)]
    private string $description = '';

    /**
     * Previous values before an update.
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $oldValues = null;

    /**
     * New values after an update.
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $newValues = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPerformedBy(): ?User
    {
        return $this->performedBy;
    }

    public function setPerformedBy(?User $performedBy): static
    {
        $this->performedBy = $performedBy;

        if ($performedBy !== null) {
            $this->actorName = $performedBy->getFullName();
            $this->actorEmail = $performedBy->getEmail();
        }

        return $this;
    }

    public function getActorName(): ?string
    {
        return $this->actorName;
    }

    public function getActorEmail(): ?string
    {
        return $this->actorEmail;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function setAction(string $action): static
    {
        $this->action = $action;

        return $this;
    }

    public function getModule(): string
    {
        return $this->module;
    }

    public function setModule(string $module): static
    {
        $this->module = $module;

        return $this;
    }

    public function getRecordId(): ?int
    {
        return $this->recordId;
    }

    public function setRecordId(?int $recordId): static
    {
        $this->recordId = $recordId;

        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getOldValues(): ?array
    {
        return $this->oldValues;
    }

    public function setOldValues(?array $oldValues): static
    {
        $this->oldValues = $oldValues;

        return $this;
    }

    public function getNewValues(): ?array
    {
        return $this->newValues;
    }

    public function setNewValues(?array $newValues): static
    {
        $this->newValues = $newValues;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}