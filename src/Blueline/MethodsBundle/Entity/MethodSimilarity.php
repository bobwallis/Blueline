<?php

namespace Blueline\MethodsBundle\Entity;

/**
 * MethodSimilarity
 */
class MethodSimilarity
{
    /**
     * @var float
     */
    private $similarity;
    /**
     * @var boolean
     */
    private $onlyDifferentOverLeadEnd;

    /**
     * @var \Blueline\MethodsBundle\Entity\Method
     */
    private $method1;

    /**
     * @var \Blueline\MethodsBundle\Entity\Method
     */
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
     * @param \Blueline\MethodsBundle\Entity\Method $method1
     *
     * @return MethodSimilarity
     */
    public function setMethod1(\Blueline\MethodsBundle\Entity\Method $method1)
    {
        $this->method1 = $method1;

        return $this;
    }

    /**
     * Get method1
     *
     * @return \Blueline\MethodsBundle\Entity\Method
     */
    public function getMethod1()
    {
        return $this->method1;
    }

    /**
     * Set method2
     *
     * @param \Blueline\MethodsBundle\Entity\Method $method2
     *
     * @return MethodSimilarity
     */
    public function setMethod2(\Blueline\MethodsBundle\Entity\Method $method2)
    {
        $this->method2 = $method2;

        return $this;
    }

    /**
     * Get method2
     *
     * @return \Blueline\MethodsBundle\Entity\Method
     */
    public function getMethod2()
    {
        return $this->method2;
    }
}

