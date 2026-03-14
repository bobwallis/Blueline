<?php

namespace Blueline\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * MethodInCollection
 */
#[ORM\Entity(repositoryClass: \Blueline\Repository\CollectionRepository::class)]
#[ORM\Table(name: 'methods_collections')]
class MethodInCollection
{
    /**
     * @var integer
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    /**
     * @var integer
     */
    #[ORM\Column(type: 'integer')]
    private $position;

    /**
     * @var \Blueline\Entity\Method
     */
    #[ORM\ManyToOne(targetEntity: Method::class, inversedBy: 'collections', cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'method_title', referencedColumnName: 'title')]
    private $method;

    /**
     * @var \Blueline\Entity\Collection
     */
    #[ORM\ManyToOne(targetEntity: Collection::class, inversedBy: 'methods', cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'collection_id', referencedColumnName: 'id')]
    private $collection;

    /**
     * Constructor
     */
    public function __construct($firstSet = array())
    {
        $this->setAll($firstSet);
    }

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

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set position
     *
     * @param  integer            $position
     * @return MethodInCollection
     */
    public function setPosition($position)
    {
        $this->position = $position;

        return $this;
    }

    /**
     * Get position
     *
     * @return integer
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * Set method
     *
     * @param  \Blueline\Entity\Method|null $method
     * @return MethodInCollection
     */
    public function setMethod(?\Blueline\Entity\Method $method)
    {
        $this->method = $method;

        return $this;
    }

    /**
     * Get method
     *
     * @return \Blueline\Entity\Methods
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * Set collection
     *
     * @param  \Blueline\Entity\Collection|null $collection
     * @return MethodInCollection
     */
    public function setCollection(?\Blueline\Entity\Collection $collection)
    {
        $this->collection = $collection;

        return $this;
    }

    /**
     * Get collection
     *
     * @return \Blueline\Entity\Collection
     */
    public function getCollection()
    {
        return $this->collection;
    }
}
