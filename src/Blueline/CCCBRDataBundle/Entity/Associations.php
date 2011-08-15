<?php

namespace Blueline\CCCBRDataBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Blueline\CCCBRDataBundle\Entity\Associations
 *
 * @ORM\Table(name="associations")
 * @ORM\Entity
 */
class Associations {
	/**
	 * @var string $abbreviation
	 *
	 * @ORM\Column(name="abbreviation", type="string", length=8, nullable=false)
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="IDENTITY")
	 */
	private $abbreviation;
	public function getAbbreviation() { return $this->abbreviation; }

	/**
	 * @var string $name
	 *
	 * @ORM\Column(name="name", type="string", length=255, nullable=false)
	 */
	private $name;
	public function setName( $name ) { $this->name = $name; }
	public function getName() { return $this->name; }

	/**
	 * @var string $link
	 *
	 * @ORM\Column(name="link", type="string", length=255, nullable=true)
	 */
	private $link;
	public function setLink( $link ) { $this->link = $link; }
	public function getLink() { return $this->link; }

	/**
	 * @var Towers
	 *
	 * @ORM\ManyToMany(targetEntity="Towers", inversedBy="affiliations")
	 * @ORM\JoinTable(name="associations_towers",
	 *   joinColumns={
	 *     @ORM\JoinColumn(name="association_abbreviation", referencedColumnName="abbreviation")
	 *   },
	 *   inverseJoinColumns={
	 *     @ORM\JoinColumn(name="tower_doveid", referencedColumnName="doveid")
	 *   }
	 * )
	 */
	private $affiliatedTowers;
	public function addAffiliatedTower( \Blueline\CCCBRDataBundle\Entity\Towers $affiliatedTowers ) { $this->affiliatedTowers[] = $affiliatedTowers; }
	public function getAffiliatedTowers() { return $this->affiliatedTowers; }

	public function __construct() {
    	$this->affiliatedTowers = new \Doctrine\Common\Collections\ArrayCollection();
	}
}
