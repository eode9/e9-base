<?php
namespace E9\Core\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class AbstractEntity
 * @package E9\Core\Entity
 *
 * @ORM\MappedSuperclass()
 */
abstract class AbstractEntity implements \JsonSerializable
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="guid", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="UUID")
     */
    public $id;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", name="created_at", nullable=true)
     */
    public $createdAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", name="updated_at", nullable=true)
     */
    public $updatedAt;

    /**
     * @ORM\PrePersist
     */
    public function onPrePersist() : void
    {
        $this->createdAt = new \DateTime();
    }

    /**
     * @ORM\PreUpdate
     */
    public function onPreUpdate() : void
    {
        $this->updatedAt = new \DateTime();
    }

    abstract public function jsonSerialize();
}
