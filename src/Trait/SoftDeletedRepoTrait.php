<?php

namespace Djc\Symfony\Trait;

trait SoftDeletedRepoTrait
{
    public function findAll()
    {
        return $this->findBy(['deletedAt' => null]);
    }
}