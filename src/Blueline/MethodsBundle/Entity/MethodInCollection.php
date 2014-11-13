<?php

namespace Blueline\MethodsBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * MethodInCollection
 */
class MethodInCollection
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var integer
     */
    private $position;

    /**
     * @var \Blueline\MethodsBundle\Entity\Methods
     */
    private $method;

    /**
     * @var \Blueline\MethodsBundle\Entity\Collection
     */
    private $collection;

    /**
     * Constructor
     */
    public function __construct( $firstSet = array() )
    {
        $this->setAll( $firstSet );
    }

    /**
     * Sets multiple variables using an array of them
     *
     * @param array $map
     */
    public function setAll($map)
    {
        foreach ($map as $key => $value) {
            $method = 'set'.str_replace( ' ', '', ucwords( str_replace( '_', ' ', $key ) ) );
            if ( is_callable( array( $this, $method ) ) ) {
                $this->$method( $value );
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
     * @param integer $position
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
     * @param \Blueline\MethodsBundle\Entity\Method $method
     * @return MethodInCollection
     */
    public function setMethod(\Blueline\MethodsBundle\Entity\Method $method = null)
    {
        $this->method = $method;

        return $this;
    }

    /**
     * Get method
     *
     * @return \Blueline\MethodsBundle\Entity\Methods 
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * Set collection
     *
     * @param \Blueline\MethodsBundle\Entity\Collection $collection
     * @return MethodInCollection
     */
    public function setCollection(\Blueline\MethodsBundle\Entity\Collection $collection = null)
    {
        $this->collection = $collection;

        return $this;
    }

    /**
     * Get collection
     *
     * @return \Blueline\MethodsBundle\Entity\Collection 
     */
    public function getCollection()
    {
        return $this->collection;
    }
}
