<?php
namespace Blueline\TowersBundle\Entity;

class OldPK
{
    /**
     * @var string $oldpk
     */
    private $oldpk;

    /**
     * @var Blueline\TowersBundle\Entity\Tower
     */
    private $tower;

    /**
     * Set tower
     *
     * @param Blueline\TowersBundle\Entity\Tower $tower
     */
    public function setTower( \Blueline\TowersBundle\Entity\Tower $tower )
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
