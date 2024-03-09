<?php

namespace Djc\Symfony\Repository;

use Djc\Symfony\Repository\BaseRepository as Repository;
use Djc\Symfony\Entity\User;
use Djc\Symfony\Trait\SoftDeletedRepoTrait;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @extends Repository<User>
 *
 * @method User|null find(string $id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    matching(Criteria $criteria)
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @method User      update(string $id, Request $request, SerializerInterface $serializer, boolean $flush = false)
 * @method User      add(Request $request, SerializerInterface $serializer, boolean $flush = false)
 * @method           delete(string $id, boolean $flush = false)
 */
class UserRepository extends Repository implements PasswordUpgraderInterface
{
    use SoftDeletedRepoTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', \get_class($user)));
        }

        $user->setPassword($newHashedPassword);

        $this->save($user, true);
    }

    public function findAdminUser(): User
    {
        return $this->findOneBy(['email' => 'pieter@dj-consultancy.nl']);
    }

}
