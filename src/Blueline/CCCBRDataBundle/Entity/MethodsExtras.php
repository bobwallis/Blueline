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
	 * @var string $method_title
	 *
	 * @ORM\Column(name="method_title", type="string", length=255, nullable=false)
	 */
	private $method_title;

	/**
	 * @var text $calls
	 *
	 * @ORM\Column(name="calls", type="text", nullable=false)
	 */
	private $calls;
	public function setCalls( $calls ) { $this->calls = serialize( $calls ); }
	public function getCalls() { return unserialize( $this->calls ); }

	/**
	 * @var text $ruleOffs
	 *
	 * @ORM\Column(name="ruleOffs", type="text", nullable=false)
	 */
	private $ruleOffs;
	public function setRuleOffs( $ruleOffs ) { $this->ruleOffs = $ruleOffs; }
	public function getRuleOffs() { return $this->ruleOffs; }
}
