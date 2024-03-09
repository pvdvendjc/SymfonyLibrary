<?php

namespace Djc\Symfony\Trait;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

trait SoftDeletableTrait
{

    #[ORM\Column(nullable: true)]
    protected ?DateTimeImmutable $deletedAt;

    public function setDeletedAt(DateTimeImmutable $deletedAt = null): self
    {
        $this->deletedAt = $deletedAt;

        return $this;
    }

    /**
     * Get the deleted at timestamp value. Will return null if
     * the entity has not been softly deleted.
     */
    public function getDeletedAt(): DateTimeImmutable|null
    {
        return $this->deletedAt;
    }

    /**
     * Check if the entity has been softly deleted.
     */
    public function isDeleted(): bool
    {
        return null !== $this->deletedAt;
    }
}