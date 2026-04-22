<?php

namespace Blueline\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * MethodSimilarity entity.
 */
#[ORM\Entity]
#[ORM\Table(name: 'methods_similar')]
#[ORM\Index(name: 'idx_methods_similar_method1_title', columns: ['method1_title'])]
#[ORM\Index(name: 'idx_methods_similar_method2_title', columns: ['method2_title'])]
class MethodSimilarity
{
    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $similarity = null;

    #[ORM\Column(name: 'onlyDifferentOverLeadEnd', type: 'boolean', nullable: true)]
    private ?bool $onlyDifferentOverLeadEnd = null;

    #[ORM\Column(name: 'onlyDifferentOverHalfLead', type: 'boolean', nullable: true)]
    private ?bool $onlyDifferentOverHalfLead = null;

    #[ORM\Column(name: 'onlyDifferentOverLeadEndAndHalfLead', type: 'boolean', nullable: true)]
    private ?bool $onlyDifferentOverLeadEndAndHalfLead = null;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Method::class, inversedBy: 'methodSimilarity1')]
    #[ORM\JoinColumn(name: 'method1_title', referencedColumnName: 'title')]
    private ?Method $method1 = null;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Method::class, inversedBy: 'methodSimilarity2')]
    #[ORM\JoinColumn(name: 'method2_title', referencedColumnName: 'title')]
    private ?Method $method2 = null;

    // Getters and setters
    public function setSimilarity($similarity)
    {
        $this->similarity = $similarity;

        return $this;
    }

    public function getSimilarity()
    {
        return $this->similarity;
    }

    public function setOnlyDifferentOverHalfLead($onlyDifferentOverHalfLead)
    {
        $this->onlyDifferentOverHalfLead = $onlyDifferentOverHalfLead;

        return $this;
    }

    public function getOnlyDifferentOverHalfLead()
    {
        return $this->onlyDifferentOverHalfLead;
    }

    public function setOnlyDifferentOverLeadEndAndHalfLead($onlyDifferentOverLeadEndAndHalfLead)
    {
        $this->onlyDifferentOverLeadEndAndHalfLead = $onlyDifferentOverLeadEndAndHalfLead;

        return $this;
    }

    public function getOnlyDifferentOverLeadEndAndHalfLead()
    {
        return $this->onlyDifferentOverLeadEndAndHalfLead;
    }

    public function setOnlyDifferentOverLeadEnd($onlyDifferentOverLeadEnd)
    {
        $this->onlyDifferentOverLeadEnd = $onlyDifferentOverLeadEnd;

        return $this;
    }

    public function getOnlyDifferentOverLeadEnd()
    {
        return $this->onlyDifferentOverLeadEnd;
    }

    public function setMethod1(Method $method1)
    {
        $this->method1 = $method1;

        return $this;
    }

    public function getMethod1()
    {
        return $this->method1;
    }

    public function setMethod2(Method $method2)
    {
        $this->method2 = $method2;

        return $this;
    }

    public function getMethod2()
    {
        return $this->method2;
    }
}
