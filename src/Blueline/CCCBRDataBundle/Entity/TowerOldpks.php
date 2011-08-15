<?php

namespace Blueline\CCCBRDataBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Blueline\CCCBRDataBundle\Entity\TowerOldpks
 *
 * @ORM\Table(name="tower_oldpks")
 * @ORM\Entity
 */
class TowerOldpks {
	/**
	 * @var string $oldpk
	 *
	 * @ORM\Column(name="oldpk", type="string", length=10, nullable=false)
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="IDENTITY")
	 */
	private $oldpk;
	public function getOldpk() { return $this->oldpk; }

	/**
	 * @var Towers
	 *
	 * @ORM\ManyToOne(targetEntity="Towers")
	 * @ORM\JoinColumns({
	 *   @ORM\JoinColumn(name="tower_doveid", referencedColumnName="doveid")
	 * })
	 */
	private $tower;
	public function setTower( \Blueline\CCCBRDataBundle\Entity\Towers $tower ) { $this->tower = $tower; }
	public function getTower() { return $this->tower; }
}
