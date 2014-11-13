<?php
namespace Blueline\AssociationsBundle\Entity;

class Association
{
    public function __toString()
    {
        return $this->getId().': '.$this->getAbbreviation();
    }

    public function __construct()
    {
        $this->towers = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * @var integer $id
     */
    private $id;

    /**
     * @var string $abbreviation
     */
    private $abbreviation;

    /**
     * @var text $name
     */
    private $name;

    /**
     * @var text $link
     */
    private $link;

    /**
     * @var Blueline\TowersBundle\Entity\Tower
     */
    private $towers;

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
     * Set abbreviation
     *
     * @param string $abbreviation
     */
    public function setAbbreviation( $abbreviation )
    {
        $this->abbreviation = $abbreviation;
    }

    /**
     * Get abbreviation
     *
     * @return string
     */
    public function getAbbreviation()
    {
        return $this->abbreviation;
    }

    /**
     * Set name
     *
     * @param text $name
     */
    public function setName( $name )
    {
        $this->name = $name;
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
    public function setLink( $link )
    {
        $this->link = $link;
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
     * Add towers
     *
     * @param Blueline\TowersBundle\Entity\Tower $towers
     */
    public function addTower( \Blueline\TowersBundle\Entity\Tower $towers )
    {
        $this->towers[] = $towers;
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
