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
	 * @var string $tower_doveid
	 *
	 * @ORM\Column(name="tower_doveid", type="string", length=10, nullable=false)
	 */
	private $tower_doveid;
	public function getTowerDoveid() { return $this->tower_doveid; }
}
