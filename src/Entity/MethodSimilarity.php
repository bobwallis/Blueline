<?php

namespace Blueline\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * MethodSimilarity
 */
#[ORM\Entity]
#[ORM\Table(name: 'methods_similar')]
class MethodSimilarity
{
    /**
     * @var float
     */
    #[ORM\Column(type: 'float', nullable: true)]
    private $similarity;

    /**
     * @var boolean
     */
    #[ORM\Column(name: 'onlydifferentoverleadend', type: 'boolean', nullable: true)]
    private $onlyDifferentOverLeadEnd;

    /**
     * @var boolean
     */
    #[ORM\Column(name: 'onlydifferentoverhalflead', type: 'boolean', nullable: true)]
    private $onlyDifferentOverHalfLead;

    /**
     * @var boolean
     */
    #[ORM\Column(name: 'onlydifferentoverleadendandhalflead', type: 'boolean', nullable: true)]
    private $onlyDifferentOverLeadEndAndHalfLead;

    /**
     * @var \Blueline\Entity\Method
     */
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Method::class, inversedBy: 'methodsimilarity1')]
    #[ORM\JoinColumn(name: 'method1_title', referencedColumnName: 'title')]
    private $method1;

    /**
     * @var \Blueline\Entity\Method
     */
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Method::class, inversedBy: 'methodsimilarity2')]
    #[ORM\JoinColumn(name: 'method2_title', referencedColumnName: 'title')]
    private $method2;


    /**
     * Set similarity
     *
     * @param float $similarity
     *
     * @return MethodSimilarity
     */
    public function setSimilarity($similarity)
    {
        $this->similarity = $similarity;

        return $this;
    }

    /**
     * Get similarity
     *
     * @return float
     */
    public function getSimilarity()
    {
        return $this->similarity;
    }

    /**
     * Set onlyDifferentOverHalfLead
     *
     * @param boolean $onlyDifferentOverHalfLead
     *
     * @return MethodSimilarity
     */
    public function setOnlyDifferentOverHalfLead($onlyDifferentOverHalfLead)
    {
        $this->onlyDifferentOverHalfLead = $onlyDifferentOverHalfLead;

        return $this;
    }

    /**
     * Get onlyDifferentOverHalfLead
     *
     * @return boolean
     */
    public function getOnlyDifferentOverHalfLead()
    {
        return $this->onlyDifferentOverHalfLead;
    }

    /**
     * Set onlyDifferentOverLeadEndAndHalfLead
     *
     * @param boolean $onlyDifferentOverLeadEndAndHalfLead
     *
     * @return MethodSimilarity
     */
    public function setOnlyDifferentOverLeadEndAndHalfLead($onlyDifferentOverLeadEndAndHalfLead)
    {
        $this->onlyDifferentOverLeadEndAndHalfLead = $onlyDifferentOverLeadEndAndHalfLead;

        return $this;
    }

    /**
     * Get onlyDifferentOverLeadEndAndHalfLead
     *
     * @return boolean
     */
    public function getOnlyDifferentOverLeadEndAndHalfLead()
    {
        return $this->onlyDifferentOverLeadEndAndHalfLead;
    }

    /**
     * Set onlyDifferentOverLeadEnd
     *
     * @param boolean $onlyDifferentOverLeadEnd
     *
     * @return MethodSimilarity
     */
    public function setOnlyDifferentOverLeadEnd($onlyDifferentOverLeadEnd)
    {
        $this->onlyDifferentOverLeadEnd = $onlyDifferentOverLeadEnd;

        return $this;
    }

    /**
     * Get onlyDifferentOverLeadEnd
     *
     * @return boolean
     */
    public function getOnlyDifferentOverLeadEnd()
    {
        return $this->onlyDifferentOverLeadEnd;
    }

    /**
     * Set method1
     *
     * @param \Blueline\Entity\Method $method1
     *
     * @return MethodSimilarity
     */
    public function setMethod1(\Blueline\Entity\Method $method1)
    {
        $this->method1 = $method1;

        return $this;
    }

    /**
     * Get method1
     *
     * @return \Blueline\Entity\Method
     */
    public function getMethod1()
    {
        return $this->method1;
    }

    /**
     * Set method2
     *
     * @param \Blueline\Entity\Method $method2
     *
     * @return MethodSimilarity
     */
    public function setMethod2(\Blueline\Entity\Method $method2)
    {
        $this->method2 = $method2;

        return $this;
    }

    /**
     * Get method2
     *
     * @return \Blueline\Entity\Method
     */
    public function getMethod2()
    {
        return $this->method2;
    }
}
