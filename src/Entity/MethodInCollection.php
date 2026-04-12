<?php

namespace Blueline\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * MethodInCollection entity
 */
#[ORM\Entity(repositoryClass: \Blueline\Repository\CollectionRepository::class)]
#[ORM\Table(name: 'methods_collections')]
#[ORM\Index(name: 'idx_methods_collections_collection_id', columns: array('collection_id'))]
#[ORM\Index(name: 'idx_methods_collections_method_title', columns: array('method_title'))]
class MethodInCollection
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'integer')]
    private int $position;

    #[ORM\ManyToOne(targetEntity: Method::class, inversedBy: 'collections', cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'method_title', referencedColumnName: 'title')]
    private Method $method;

    #[ORM\ManyToOne(targetEntity: Collection::class, inversedBy: 'methods', cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'collection_id', referencedColumnName: 'id')]
    private Collection $collection;

    public function __construct($firstSet = array())
    {
        $this->setAll($firstSet);
    }

    /**
     * Convert the relation to a short readable label.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getMethod().' in '.$this->getCollection();
    }

    /**
     * Sets multiple variables using an array of them
     *
     * @param array $map
     */
    public function setAll($map)
    {
        foreach ($map as $key => $value) {
            $method = 'set'.str_replace(' ', '', ucwords(str_replace('_', ' ', $key)));
            if (is_callable(array( $this, $method ))) {
                $this->$method($value);
            }
        }
        return $this;
    }

    // Getters and setters
    public function getId()
    {
        return $this->id;
    }

    public function setPosition($position)
    {
        $this->position = $position;
        return $this;
    }

    public function getPosition()
    {
        return $this->position;
    }

    public function setMethod(?\Blueline\Entity\Method $method)
    {
        $this->method = $method;
        return $this;
    }

    public function getMethod()
    {
        return $this->method;
    }

    public function setCollection(?\Blueline\Entity\Collection $collection)
    {
        $this->collection = $collection;
        return $this;
    }

    public function getCollection()
    {
        return $this->collection;
    }
}
