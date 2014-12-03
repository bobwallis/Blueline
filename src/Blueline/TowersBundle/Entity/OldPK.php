<?php
namespace Blueline\TowersBundle\Entity;

class OldPK
{
    // Constructor
    public function __construct($firstSet = array())
    {
        $this->setAll($firstSet);
    }

    // Casting helpers
    public function __toString()
    {
        return 'OldPK:'.$this->getOldpk();
    }

    public function __toArray($depth = 1)
    {
        $objectVars = get_object_vars($this);
        array_walk($objectVars, function (&$v, $k) use ($depth) {
            if ($k == 'tower') {
                $v = null;
            }
        }, $depth);

        return array_filter($objectVars);
    }

    // setAll helper
    public function setAll($map)
    {
        foreach ($map as $key => $value) {
            $method = 'set'.ucwords($key);
            if (is_callable(array( $this, $method ))) {
                $this->$method($value);
            }
        }
    }

    // Variables
    /**
     * @var string $oldpk
     */
    private $oldpk;

    /**
     * @var Blueline\TowersBundle\Entity\Tower
     */
    private $tower;

    // Getters and setters
    /**
     * Set tower
     *
     * @param Blueline\TowersBundle\Entity\Tower $tower
     */
    public function setTower(\Blueline\TowersBundle\Entity\Tower $tower)
    {
        $this->tower = $tower;
    }

    /**
     * Get tower
     *
     * @return Blueline\TowersBundle\Entity\Tower
     */
    public function getTower()
    {
        return $this->tower;
    }

    /**
     * Set oldpk
     *
     * @param  string $oldpk
     * @return OldPK
     */
    public function setOldpk($oldpk)
    {
        $this->oldpk = $oldpk;

        return $this;
    }

    /**
     * Get oldpk
     *
     * @return string
     */
    public function getOldpk()
    {
        return $this->oldpk;
    }
}
