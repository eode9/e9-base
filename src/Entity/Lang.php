<?php

namespace E9\Core\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="core_langs")
 */
class Lang extends AbstractEntity
{
    /**
     * @ORM\Column(type="string", length=64)
     */
    public $name;

    /**
     * @ORM\Column(type="string", length=64)
     */
    public $iso;

    public function jsonSerialize()
    {
        return [
          'uuid' => $this->id,
          'name' => $this->name,
          'iso' => $this->iso,
        ];
    }
}
