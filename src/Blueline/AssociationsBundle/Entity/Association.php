<?php
namespace Blueline\AssociationsBundle\Entity;

class Association
{
    // Constructor
    public function __construct($firstSet = array())
    {
        $this->towers = new \Doctrine\Common\Collections\ArrayCollection();
        $this->setAll($firstSet);
    }

    // Casting helpers
    public function __toString()
    {
        return 'Association:'.$this->getId();
    }

    public function __toArray()
    {
        $objectVars = get_object_vars($this);
        array_walk($objectVars, function (&$v, $k) {
            switch ($k) {
                // Don't try to drill down into sub-entities
                case 'towers':
                    $v = null;
                    break;
            }
        });

        return array_filter($objectVars);
    }

    // setAll helper
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

    // Variables
    /**
     * @var string $id
     */
    private $id;

    /**
     * @var text $name
     */
    private $name;

    /**
     * @var text $link
     */
    private $link;

    /**
     * @var array $outline
     */
    private $outline;

    /**
     * @var Blueline\TowersBundle\Entity\Tower
     */
    private $towers;

    // Getters and setters
    /**
     * Set id
     *
     * @param string $id
     */
    public function setId($id)
    {
        $this->id = $id;

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
     * Set name
     *
     * @param text $name
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return text
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set link
     *
     * @param text $link
     */
    public function setLink($link)
    {
        $this->link = $link;

        return $this;
    }

    /**
     * Get link
     *
     * @return text
     */
    public function getLink()
    {
        return $this->link;
    }

    /**
     * Set outline
     *
     * @param array $outline
     */
    public function setOutline($outline)
    {
        $this->outline = $outline;

        return $this;
    }

    /**
     * Get outline
     *
     * @return array
     */
    public function getOutline()
    {
        return $this->outline;
    }

    /**
     * Add towers
     *
     * @param Blueline\TowersBundle\Entity\Tower $towers
     */
    public function addTower(\Blueline\TowersBundle\Entity\Tower $towers)
    {
        $this->towers[] = $towers;

        return $this;
    }

    /**
     * Get towers
     *
     * @return Doctrine\Common\Collections\Collection
     */
    public function getTowers()
    {
        return $this->towers;
    }

    /**
     * Remove towers
     *
     * @param Blueline\TowersBundle\Entity\Tower $towers
     */
    public function removeTower(\Blueline\TowersBundle\Entity\Tower $towers)
    {
        $this->towers->removeElement($towers);
    }
}
