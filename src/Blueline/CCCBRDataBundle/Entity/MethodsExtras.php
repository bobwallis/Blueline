<?php

namespace Blueline\CCCBRDataBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Blueline\CCCBRDataBundle\Entity\MethodsExtras
 *
 * @ORM\Table(name="methods_extras")
 * @ORM\Entity
 */
class MethodsExtras {
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
	 * @var text $calls
	 *
	 * @ORM\Column(name="calls", type="text", nullable=false)
	 */
	private $calls;
	public function setCalls( $calls ) { $this->calls = serialize( $calls ); }
	public function getCalls() { return unserialize( $this->calls ); }

	/**
	 * @var text $ruleoffs
	 *
	 * @ORM\Column(name="ruleOffs", type="text", nullable=false)
	 */
	private $ruleoffs;
	public function setRuleOffs( $ruleOffs ) { $this->ruleoffs = serialize( $ruleoffs ); }
	public function getRuleoffs() { return unserialize( $this->ruleOffs ); }

	/**
	 * @var Methods
	 *
	 * @ORM\OneToOne(targetEntity="Methods")
	 * @ORM\JoinColumns({
	 *   @ORM\JoinColumn(name="method_title", referencedColumnName="title")
	 * })
	 */
	private $method;
	public function setMethod( \Blueline\CCCBRDataBundle\Entity\Methods $method ) { $this->method = $method; }
	public function getMethod() { return $this->methodTitle; }
}
