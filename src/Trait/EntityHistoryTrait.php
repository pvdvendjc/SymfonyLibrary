<?php

namespace Djc\Symfony\Trait;

use Djc\Symfony\Entity\User;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Context;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;

trait EntityHistoryTrait
{

    #[ORM\Column]
    #[Context([DateTimeNormalizer::FORMAT_KEY => DateTimeInterface::RFC3339])]
    private \DateTimeImmutable $createdAt;

    #[ORM\ManyToOne(cascade: [ 'persist' ])]
    #[ORM\JoinColumn(nullable: false)]
    private User $createdBy;

    #[ORM\Column]
    #[Context([DateTimeNormalizer::FORMAT_KEY => DateTimeInterface::RFC3339])]
    private \DateTimeImmutable $updatedAt;

    #[ORM\ManyToOne(cascade: [ 'persist' ])]
    #[ORM\JoinColumn(nullable: false)]
    private User $updatedBy;

    private function initHistory(): void
    {
        $this->setCreatedBy(new User());
        $this->setCreatedAt(new \DateTimeImmutable());
        $this->setUpdatedBy(new User());
        $this->setUpdatedAt(new \DateTimeImmutable());
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(string|array|User $createdBy): self
    {
//        if (is_string($createdBy)) {
//            $createdBy = $this->em->getRepository(User::class)->find($createdBy);
//        }
//        if (is_array($createdBy)) {
//            $createdBy = $this->em->getRepository(User::class)->find($createdBy['id']);
//        }
        $this->createdBy = $createdBy;

        return $this;
    }

    public function getUpdatedBy(): ?User
    {
        return $this->updatedBy;
    }

    public function setUpdatedBy(string|array|User $updatedBy): self
    {
//        if (is_string($updatedBy)) {
//            $updatedBy = $this->em->getRepository(User::class)->find($updatedBy);
//        }
//        if (is_array($updatedBy)) {
//            $updatedBy = $this->em->getRepository(User::class)->find($updatedBy['id']);
//        }
        $this->updatedBy = $updatedBy;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable|string|array $createdAt): self
    {
//        if (is_string($createdAt)) {
//            $createdAt = new \DateTimeImmutable($createdAt);
//        }
//        if (is_array($createdAt)) {
//            $createdAt = new \DateTimeImmutable($createdAt['date']);
//        }
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable|string|array $updatedAt): self
    {
//        if (is_string($updatedAt)) {
//            $updatedAt = new \DateTimeImmutable($updatedAt);
//        }
//        if (is_array($updatedAt)) {
//            $updatedAt = new \DateTimeImmutable($updatedAt['date']);
//        }
        $this->updatedAt = $updatedAt;

        return $this;
    }

}