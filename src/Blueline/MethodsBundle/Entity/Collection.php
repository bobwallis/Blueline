<?php

namespace Blueline\MethodsBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Collection
 */
class Collection
{
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

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->methods = new \Doctrine\Common\Collections\ArrayCollection();
    }

    public function __toString() {
        return $this->getName();
    }

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
