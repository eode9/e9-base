<?php

namespace E9\Core\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations\Document;
use Doctrine\ODM\MongoDB\Mapping\Annotations\Field;

/**
 * @Document(
 *     collection={"name"="core_lang"},
 *     repositoryClass="E9\Core\Repository\LangRepository"
 * )
 */
class Lang extends AbstractDocument
{
    /** @Field(type="string") */
    public $name;

    /** @Field(type="string") */
    public $iso;

    public function jsonSerialize()
    {
        return [
          'id' => $this->id,
          'name' => $this->name,
          'iso' => $this->iso,
        ];
    }
}
