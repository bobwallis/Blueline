<?php

namespace Blueline\CCCBRDataBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Blueline\CCCBRDataBundle\Entity\MethodsExtras
 *
 * @ORM\Table(name="methods_duplicates")
 * @ORM\Entity
 */
class MethodsDuplicates {
	/**
	 * @var integer $id
	 *
	 * @ORM\Column(name="id", type="integer", nullable=false)
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="IDENTITY")
	 */
	private $id;
	public function getId() { return $this->id; }

	/**
	 * @ORM\ManyToOne(targetEntity="Methods")
	 * @ORM\JoinColumn(name="actual", referencedColumnName="title")
	 */
	private $method;

	/**
	 * @var string $actual
	 *
	 * @ORM\Column(name="actual", type="string", length=255, nullable=false)
	 */
	private $actual;

	/**
	 * @var string $rung
	 *
	 * @ORM\Column(name="rung", type="string", length=255, nullable=false)
	 */
	private $rung;
	public function setRung( $rung ) { $this->rung = $rung; }
	public function getRung() { return $this->rung; }

	/**
	 * @var string $rung_location
	 *
	 * @ORM\Column(name="rung_location", type="string", length=255, nullable=true)
	 */
	private $rung_location;
	public function setRungLocation( $rung_location ) { $this->rung_location = $rung_location; }
	public function getRungLocation() { return $this->rung_location; }

	/**
	 * @var string $rung_date
	 *
	 * @ORM\Column(name="rung_date", type="date", nullable=true)
	 */
	private $rung_date;
	public function setRungDate( $rung_date ) { $this->rung_date = $rung_date; }
	public function getRungDate() { return $this->rung_date; }

	/**
	 * @var string $rung_rwRef
	 *
	 * @ORM\Column(name="rung_rwRef", type="string", length=30, nullable=true)
	 */
	private $rung_rwRef;
	public function setRungRWRef( $rung_rwRef ) { $this->rung = $rung_rwRef; }
	public function getRungRWRef() { return $this->rung_rwRef; }

}
