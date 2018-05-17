<?php

namespace E9\Core\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations\Field;
use Doctrine\ODM\MongoDB\Mapping\Annotations\HasLifecycleCallbacks;
use Doctrine\ODM\MongoDB\Mapping\Annotations\Id;
use Doctrine\ODM\MongoDB\Mapping\Annotations\MappedSuperclass;
use Doctrine\ODM\MongoDB\Mapping\Annotations\PrePersist;

/**
 * Class AbstractDocument
 * @package E9\Core\Document
 *
 * @MappedSuperclass()
 * @HasLifecycleCallbacks
 */
abstract class AbstractDocument implements \JsonSerializable
{
    /** @Id */
    public $id;

    /** @Field(type="date") */
    public $createdAt;

    /** @Field(type="date") */
    public $updatedAt;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    /** @PrePersist( */
    public function prePersist() : void
    {
        $this->updatedAt = new \DateTime();
    }

    abstract public function jsonSerialize();
}
