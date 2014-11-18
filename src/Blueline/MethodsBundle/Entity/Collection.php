<?php

namespace Blueline\MethodsBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Collection
 */
class Collection
{
    // Constructor
    public function __construct( $firstSet = array() )
    {
        $this->methods = new \Doctrine\Common\Collections\ArrayCollection();
        $this->setAll( $firstSet );
    }

    // Casting helpers
    public function __toString() {
        return 'Collection:'.$this->getId();
    }

    public function __toArray()
    {
        $objectVars = get_object_vars($this);
        array_walk( $objectVars, function( &$v, $k ) {
            // Filter out id because that's only really meaningful internally, and don't try to drill down into sub-entities
            if( $k == 'id' || $k == 'methods' ) {
                $v = null;
            }
        } );
        return array_filter( $objectVars );
    }

    // setAll helper
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

    // Variables
    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $description;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $methods;

    // Getters and setters
    /**
     * Set id
     *
     * @param string $id
     * @return Collection
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get id
     *
     * @return string 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return Collection
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return Collection
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string 
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Add methods
     *
     * @param \Blueline\MethodsBundle\Entity\MethodInCollection $methods
     * @return Collection
     */
    public function addMethod(\Blueline\MethodsBundle\Entity\MethodInCollection $method)
    {
        $this->methods[] = $method;

        return $this;
    }

    /**
     * Remove methods
     *
     * @param \Blueline\MethodsBundle\Entity\MethodInCollection $method
     */
    public function removeMethod(\Blueline\MethodsBundle\Entity\MethodInCollection $method)
    {
        $this->methods->removeElement($method);
    }

    /**
     * Get methods
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getMethods()
    {
        return $this->methods;
    }
}
