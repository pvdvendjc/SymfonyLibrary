<?php

namespace Djc\Symfony\Repository;

use Djc\Symfony\Interface\Entity;
use Djc\Symfony\Trait\EntityHistoryTrait;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use LogicException;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;

class BaseRepository extends EntityRepository implements ServiceEntityRepository
{
    public function __construct(private readonly ManagerRegistry $registry, private readonly string $entityClass)
    {
        $manager = $this->registry->getManager($this->entityClass);

        if ($manager === null) {
            throw new LogicException(sprintf(
                'Could not find the entity manager for class "%s". Check your Doctrine configuration to make sure it is configured to load this entity’s metadata.',
                $entityClass
            ));
        }
        parent::__construct($manager, $manager->getClassMetadata($entityClass));
    }

    public function delete(string $id, bool $flush = false): void
    {
        $entity = $this->find($id);
        $this->getEntityManager()->remove($entity);

        if ($flush) $this->getEntityManager()->flush();

    }

    public function updateOrCreate(string|null $id, Request $request, SerializerInterface $serializer, bool $flush = false): object
    {
        if (empty($id)) {
            $entity = new ($this->entityClass)();
            if (array_key_exists(Entity::class, class_implements($entity))) {
                $entity->init();
            }
            $entity->setDoctrineManager($this->registry);
            $entity->setId(Uuid::uuid4());
        } else {
            $entity = $this->find($id);
        }
        $entity->fromArray(json_decode($request->getContent(), true));

        if (in_array(EntityHistoryTrait::class, class_uses($entity))) {
            $repo = $this->getEntityManager()->getRepository(get_class($entity->getUpdatedBy()));
            $user = $repo->find($request->getSession()->get('userId'));
            if (empty($id)) {
                $entity->setCreatedAt(new \DateTimeImmutable());
                $entity->setCreatedBy($user);
            }
            $entity->setUpdatedAt(new \DateTimeImmutable());
            $entity->setUpdatedBy($user);
        }
        $this->getEntityManager()->persist($entity);

        if ($flush) $this->getEntityManager()->flush();

        return $entity;

    }
}