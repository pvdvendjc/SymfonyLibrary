<?php

namespace Djc\Symfony\Trait;

use Djc\Symfony\Entity\BaseEntity;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;

trait EntityTrait
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'Djc\Symfony\Service\AbstractUuidGenerator')]
    #[ORM\Column(type: 'uuid')]
    private ?string $id = null;

    private ManagerRegistry | ObjectManager $em;

    public function setDoctrineManager(ManagerRegistry | ObjectManager $em): void
    {
        $this->em = $em;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(?string $id): BaseEntity
    {
        $this->id = $id;
        return $this;
    }

    public function fromArray(array $data = []): void {

        if (array_key_exists('createdAt', $data)) unset($data['createdAt']);
        if (array_key_exists('updatedAt', $data)) unset($data['updatedAt']);
        if (array_key_exists('createdBy', $data)) unset($data['createdBy']);
        if (array_key_exists('updatedBy', $data)) unset($data['updatedBy']);

        foreach ($data as $property => $value) {
            $method = "set". ucfirst($property);
            $relatedClass = false;
            if (method_exists($this, $method)) {
                $getMethod = "get". ucfirst($property);
                if (!method_exists($this, $getMethod)) {
                    $getMethod = "is". ucfirst($property);
                }
                $type = gettype($this->$getMethod());
                if ($type === 'object' || is_array($value)) {
                    $oldValue = $this->$getMethod();
                    if ($oldValue !== null &&
                        (
                            (
                                $type === 'object' && method_exists($oldValue, 'getId') && ($oldValue->getId() ?? '') !== ''
                            ) || (
                                $oldValue instanceof \DateTimeImmutable || $oldValue instanceof \DateTime
                            )
                        )
                    ) {
                        $class = get_class($oldValue);
                        if ($oldValue instanceof \DateTimeImmutable || $oldValue instanceof \DateTime) {
                            if (is_array($value)) {
                                $value = $value['date'];
                            }
                            $value = new $class($value);
                        } else {
                            $value = $this->em->getRepository($class)->find($value['id']);
                        }
                    } else {
                        $relatedClass = true;
                    }
                }
                if (!$relatedClass) {
                    $this->$method($value);
                }
            }
        }
    }

    public function toArray(): array {
        $data = ['id' => $this->getId()];
        $properties = get_object_vars($this);
        foreach ($properties as $property => $value) {
            if (is_bool($this->$property)) {
                $method = "is{$property}";  // "get"-method for a boolean var is "is"
            } else {
                $method = "get{$property}";
            }
            if (method_exists($this, $method)) {
                $data[$property] = $this->$method();
            }
        }
        return $data;
    }
}