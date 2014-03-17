<?php
namespace Blueline\MethodsBundle\Entity;

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

    /**
     * Set id
     *
     * @param  string     $id
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
     * @param  string     $name
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
     * Add methods
     *
     * @param  \Blueline\MethodsBundle\Entity\Method $methods
     * @return Collection
     */
    public function addMethod(\Blueline\MethodsBundle\Entity\Method $methods)
    {
        $this->methods[] = $methods;

        return $this;
    }

    /**
     * Remove methods
     *
     * @param \Blueline\MethodsBundle\Entity\Method $methods
     */
    public function removeMethod(\Blueline\MethodsBundle\Entity\Method $methods)
    {
        $this->methods->removeElement($methods);
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
